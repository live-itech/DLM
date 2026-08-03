<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Totals;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('Admin');

        Warehouse::create(['name' => 'Gudang Utama', 'is_default' => true, 'is_active' => true]);

        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
        $this->customer = Customer::create([
            'code' => 'C001', 'name' => 'PT Uji', 'payment_term_days' => 30, 'is_active' => true,
        ]);
        $this->product = Product::create([
            'code' => 'P001', 'name' => 'Barang A', 'unit_id' => $unit->id,
            'cost_price' => 5000, 'sell_price' => 10000, 'stock' => 100, 'min_stock' => 0, 'is_active' => true,
        ]);
    }

    /** Buat SO draft + 1 item, totals terhitung. */
    private function makeDraftSO(float $qty = 2, float $price = 10000): SalesOrder
    {
        $so = SalesOrder::create([
            'so_number' => 'SO/TEST/' . uniqid(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
            'is_taxable' => true,
            'ppn_rate' => 11,
            'discount' => 0,
        ]);
        $sub = $qty * $price;
        $so->items()->create([
            'product_id' => $this->product->id, 'qty' => $qty, 'sell_price' => $price,
            'discount' => 0, 'subtotal' => $sub,
        ]);
        $so->update(Totals::compute($sub, 0, true, 11));

        return $so->fresh();
    }

    /** SO draft -> confirmed (via endpoint, memotong stok). */
    private function confirm(SalesOrder $so): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales-orders.confirm', $so))
            ->assertRedirect();
    }

    /** SO confirmed -> punya invoice. */
    private function invoice(SalesOrder $so): Invoice
    {
        $this->actingAs($this->admin)
            ->post(route('sales-orders.to-invoice', $so), ['date' => now()->toDateString()])
            ->assertRedirect();

        return $so->fresh()->invoice;
    }

    // ---------------------------------------------------------------
    // ALUR UTAMA
    // ---------------------------------------------------------------

    public function test_konfirmasi_memotong_stok(): void
    {
        $so = $this->makeDraftSO(qty: 3);
        $this->confirm($so);

        $this->assertSame('confirmed', $so->fresh()->status);
        $this->assertTrue((bool) $so->fresh()->stock_deducted);
        $this->assertEquals(97, (float) $this->product->fresh()->stock);
    }

    public function test_so_tetap_confirmed_setelah_dibuat_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->assertNotNull($inv);
        $this->assertSame('confirmed', $so->fresh()->status, 'SO tidak boleh langsung Selesai saat invoice dibuat.');
        $this->assertSame('unpaid', $inv->status);
    }

    public function test_tandai_dikirim_setelah_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $this->invoice($so);

        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertRedirect();

        $this->assertSame('shipped', $so->fresh()->status);
    }

    public function test_pelunasan_menjadikan_so_selesai(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)
            ->post(route('sales-orders.ship', $so))->assertRedirect();

        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(),
            'amount' => $inv->total,
        ])->assertRedirect();

        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertSame('completed', $so->fresh()->status);
    }

    public function test_pelunasan_tanpa_dikirim_langsung_selesai(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        // Belum ditandai dikirim (masih confirmed), langsung lunas.
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(),
            'amount' => $inv->total,
        ])->assertRedirect();

        $this->assertSame('completed', $so->fresh()->status);
    }

    public function test_pembayaran_sebagian_tidak_menjadikan_selesai(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(),
            'amount' => 1000, // sebagian
        ])->assertRedirect();

        $this->assertSame('partial', $inv->fresh()->status);
        $this->assertSame('confirmed', $so->fresh()->status, 'Belum lunas, SO belum boleh Selesai.');
    }

    // ---------------------------------------------------------------
    // SKENARIO HUMAN ERROR (harus ditolak)
    // ---------------------------------------------------------------

    public function test_tidak_bisa_dikirim_tanpa_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so); // confirmed, tapi belum ada invoice

        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertForbidden();
        $this->assertSame('confirmed', $so->fresh()->status);
    }

    public function test_tidak_bisa_dikirim_saat_draft(): void
    {
        $so = $this->makeDraftSO();

        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertForbidden();
        $this->assertSame('draft', $so->fresh()->status);
    }

    public function test_tidak_bisa_dikirim_dua_kali(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $this->invoice($so);
        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertRedirect();

        // sudah shipped -> tekan lagi harus ditolak
        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertForbidden();
        $this->assertSame('shipped', $so->fresh()->status);
    }

    public function test_tidak_bisa_dikirim_saat_sudah_selesai(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(), 'amount' => $inv->total,
        ])->assertRedirect();

        $this->assertSame('completed', $so->fresh()->status);
        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertForbidden();
    }

    public function test_tidak_bisa_dobel_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $this->invoice($so);

        $this->actingAs($this->admin)
            ->post(route('sales-orders.to-invoice', $so), ['date' => now()->toDateString()])
            ->assertStatus(422);
    }

    public function test_tidak_bisa_invoice_dari_draft(): void
    {
        $so = $this->makeDraftSO(); // masih draft

        $this->actingAs($this->admin)
            ->post(route('sales-orders.to-invoice', $so), ['date' => now()->toDateString()])
            ->assertForbidden();
    }

    // ---- Fitur edit invoice yang sudah dihapus ----

    public function test_route_edit_invoice_sudah_tidak_ada(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        // GET /invoices/{id}/edit -> tidak ada route
        $this->actingAs($this->admin)->get("/invoices/{$inv->id}/edit")->assertNotFound();
        // PUT /invoices/{id} -> method tidak diizinkan
        $this->actingAs($this->admin)->put("/invoices/{$inv->id}", [])->assertStatus(405);
    }

    public function test_halaman_invoice_tidak_menampilkan_edit_item(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)->get(route('invoices.show', $inv))
            ->assertOk()
            ->assertDontSee('Edit Item');
    }

    // ---- Tombol dikirim di view ----

    public function test_tombol_dikirim_muncul_setelah_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);

        // sebelum invoice: belum ada tombol
        $this->actingAs($this->admin)->get(route('sales-orders.show', $so))
            ->assertOk()->assertDontSee('Tandai Dikirim');

        $this->invoice($so);

        // sesudah invoice: tombol muncul
        $this->actingAs($this->admin)->get(route('sales-orders.show', $so->fresh()))
            ->assertOk()->assertSee('Tandai Dikirim');
    }

    // ---- Pembayaran ----

    public function test_pembayaran_melebihi_sisa_ditolak(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)
            ->from(route('invoices.show', $inv))
            ->post(route('invoices.payments.store', $inv), [
                'date' => now()->toDateString(),
                'amount' => $inv->total + 50000, // lebih dari sisa
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('unpaid', $inv->fresh()->status);
        $this->assertSame('confirmed', $so->fresh()->status);
    }

    public function test_bayar_invoice_yang_sudah_lunas_ditolak(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(), 'amount' => $inv->total,
        ])->assertRedirect();

        // sudah lunas -> pembayaran lagi ditolak
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv->fresh()), [
            'date' => now()->toDateString(), 'amount' => 1000,
        ])->assertStatus(422);
    }

    public function test_batalkan_tidak_tersedia_setelah_ada_invoice(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $this->invoice($so);

        $this->actingAs($this->admin)->post(route('sales-orders.cancel', $so))->assertForbidden();
        $this->assertSame('confirmed', $so->fresh()->status);
    }

    // ---------------------------------------------------------------
    // PEMBUATAN SO — cegah double-submit
    // ---------------------------------------------------------------

    /** Payload create SO dengan 1 item. */
    private function createPayload(float $qty, float $price = 10000): array
    {
        return [
            'customer_id' => $this->customer->id,
            'date' => now()->toDateString(),
            'is_taxable' => 1,
            'ppn_rate' => 11,
            'discount' => 0,
            'items' => [
                ['product_id' => $this->product->id, 'qty' => $qty, 'sell_price' => $price, 'discount' => 0],
            ],
        ];
    }

    public function test_pembuatan_so_duplikat_diabaikan(): void
    {
        $payload = $this->createPayload(qty: 1, price: 567500);

        $this->actingAs($this->admin)->post(route('sales-orders.store'), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('sales-orders.store'), $payload)->assertRedirect();

        $this->assertEquals(1, SalesOrder::count(), 'SO identik beruntun tidak boleh tercatat dua kali.');
    }

    public function test_so_beda_total_tetap_dibuat(): void
    {
        $this->actingAs($this->admin)->post(route('sales-orders.store'), $this->createPayload(qty: 1, price: 10000))->assertRedirect();
        $this->actingAs($this->admin)->post(route('sales-orders.store'), $this->createPayload(qty: 2, price: 10000))->assertRedirect();

        $this->assertEquals(2, SalesOrder::count(), 'SO dengan total berbeda tetap dibuat.');
    }

    // ---------------------------------------------------------------
    // PEMBAYARAN — cegah double-submit
    // ---------------------------------------------------------------

    public function test_pembayaran_duplikat_diabaikan(): void
    {
        $so = $this->makeDraftSO(qty: 5, price: 10000); // total 55.500
        $this->confirm($so);
        $inv = $this->invoice($so);

        $payload = ['date' => now()->toDateString(), 'amount' => 20000, 'method' => 'COD'];
        // dua submit identik beruntun (simulasi klik ganda)
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), $payload)->assertRedirect();
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv->fresh()), $payload)->assertRedirect();

        $inv->refresh();
        $this->assertEquals(1, $inv->payments()->count(), 'Pembayaran duplikat tidak boleh tercatat dua kali.');
        $this->assertEquals(20000, (float) $inv->paid_amount);
        $this->assertSame('partial', $inv->status);
    }

    public function test_pembayaran_beda_jumlah_tetap_tercatat(): void
    {
        $so = $this->makeDraftSO(qty: 5, price: 10000);
        $this->confirm($so);
        $inv = $this->invoice($so);

        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(), 'amount' => 20000,
        ])->assertRedirect();
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv->fresh()), [
            'date' => now()->toDateString(), 'amount' => 15000,
        ])->assertRedirect();

        $inv->refresh();
        $this->assertEquals(2, $inv->payments()->count(), 'Pembayaran dengan jumlah berbeda tetap tercatat.');
        $this->assertEquals(35000, (float) $inv->paid_amount);
        $this->assertSame('partial', $inv->status);
    }

    // ---------------------------------------------------------------
    // EDIT ITEM SO (draft / confirmed / shipped)
    // ---------------------------------------------------------------

    /** Payload update SO dengan 1 item. */
    private function updatePayload(float $qty, float $price = 10000, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'date' => now()->toDateString(),
            'is_taxable' => 1,
            'ppn_rate' => 11,
            'discount' => 0,
            'items' => [
                ['product_id' => $this->product->id, 'qty' => $qty, 'sell_price' => $price, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_edit_draft_tidak_menyentuh_stok(): void
    {
        $so = $this->makeDraftSO(qty: 3); // draft, stok belum dipotong (tetap 100)

        $this->actingAs($this->admin)
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 7))
            ->assertRedirect();

        $this->assertEquals(100, (float) $this->product->fresh()->stock);
        $this->assertEquals(7, (float) $so->fresh()->items->first()->qty);
    }

    public function test_edit_saat_confirmed_menyesuaikan_stok(): void
    {
        $so = $this->makeDraftSO(qty: 3); // stok 100
        $this->confirm($so);              // stok 97

        $this->actingAs($this->admin)
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 5))
            ->assertRedirect();

        // kembalikan 3 (->100), potong 5 (->95)
        $this->assertEquals(95, (float) $this->product->fresh()->stock);
        $this->assertSame('confirmed', $so->fresh()->status);
        $this->assertEquals(55500, (float) $so->fresh()->total); // 5*10000*1.11
    }

    public function test_edit_saat_shipped_memperbarui_rincian_dan_total_invoice(): void
    {
        $so = $this->makeDraftSO(qty: 2, price: 10000);
        $this->confirm($so);
        $inv = $this->invoice($so);
        $this->actingAs($this->admin)->post(route('sales-orders.ship', $so))->assertRedirect();

        $this->actingAs($this->admin)
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 4))
            ->assertRedirect();

        $so->refresh();
        $inv->refresh();
        $this->assertSame('shipped', $so->status);
        $this->assertEquals(4, (float) $so->items->first()->qty, 'Rincian invoice (item SO) ikut berubah.');
        $this->assertEquals(44400, (float) $so->total);   // 4*10000*1.11
        $this->assertEquals(44400, (float) $inv->total);  // total invoice ikut
        $this->assertEquals(96, (float) $this->product->fresh()->stock); // 100 -2 (confirm) +2 -4 (edit)
    }

    public function test_edit_ditolak_saat_completed(): void
    {
        $so = $this->makeDraftSO();
        $this->confirm($so);
        $inv = $this->invoice($so);
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(), 'amount' => $inv->total,
        ])->assertRedirect();

        $this->assertSame('completed', $so->fresh()->status);
        $this->actingAs($this->admin)->get(route('sales-orders.edit', $so))->assertForbidden();
        $this->actingAs($this->admin)
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 9))
            ->assertForbidden();
    }

    public function test_edit_ditolak_jika_total_di_bawah_yang_sudah_dibayar(): void
    {
        $so = $this->makeDraftSO(qty: 5, price: 10000); // total 55500
        $this->confirm($so);
        $inv = $this->invoice($so);

        // bayar sebagian 30.000
        $this->actingAs($this->admin)->post(route('invoices.payments.store', $inv), [
            'date' => now()->toDateString(), 'amount' => 30000,
        ])->assertRedirect();
        $this->assertSame('partial', $inv->fresh()->status);

        // coba turunkan jadi 1 item (total 11.100) < 30.000 -> ditolak
        $this->actingAs($this->admin)
            ->from(route('sales-orders.edit', $so))
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 1))
            ->assertSessionHasErrors('items');

        // data tak berubah
        $this->assertEquals(55500, (float) $inv->fresh()->total);
        $this->assertEquals(5, (float) $so->fresh()->items->first()->qty);
    }

    public function test_edit_stok_tidak_cukup_ditolak_dan_rollback(): void
    {
        $so = $this->makeDraftSO(qty: 3); // stok 97 setelah confirm
        $this->confirm($so);

        // minta qty 200 padahal stok cuma 100 (setelah kembalikan 3) -> gagal
        $this->actingAs($this->admin)
            ->from(route('sales-orders.edit', $so))
            ->put(route('sales-orders.update', $so), $this->updatePayload(qty: 200))
            ->assertSessionHasErrors('stock');

        // rollback: stok & item tetap seperti semula
        $this->assertEquals(97, (float) $this->product->fresh()->stock);
        $this->assertEquals(3, (float) $so->fresh()->items->first()->qty);
    }
}
