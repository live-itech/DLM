<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Gudang default (single) ----------
        Warehouse::firstOrCreate(
            ['is_default' => true],
            ['name' => 'Gudang Utama', 'address' => 'Jl. Kodiklat TNI Buaran Hankam No. 59, Serpong, Tangerang Selatan', 'is_active' => true]
        );

        // ---------- Profil perusahaan (dari dokumen OSS/NIB) ----------
        Setting::putMany([
            'company_name'    => 'PT DIMAS LOVE MEDIKA',
            'company_npwp'    => '',
            'company_nib'     => '0108250170791',
            'company_izin'    => '01082501707910001',
            'company_kbli'    => '46791 - Perdagangan Besar Alat Kesehatan dan Laboratorium untuk Manusia',
            'company_address' => 'Jl. Kodiklat TNI Buaran Hankam No. 59 RT.001/RW.003, Kel. Buaran, Kec. Serpong, Kota Tangerang Selatan, Banten 15310',
            'company_phone'   => '021-38939414 / 0859-3319-1346',
            'company_email'   => 'anggihdimas@gmail.com',
            'company_pjt'     => 'Nia Love Fiana Puteri (D.III Farmasi)',
            // Pajak
            'ppn_rate'            => '11',
            'fp_transaction_code' => '04',
            'fp_prefix'           => '',
        ]);

        // ---------- Satuan ----------
        $units = [];
        foreach ([['Pieces', 'pcs'], ['Box', 'box'], ['Unit', 'unit'], ['Set', 'set'], ['Pack', 'pack'], ['Lusin', 'lsn']] as [$name, $symbol]) {
            $units[$symbol] = Unit::firstOrCreate(['name' => $name], ['symbol' => $symbol]);
        }

        // ---------- Kategori ----------
        $cats = [];
        foreach ([
            'Alkes Nonelektromedik Steril',
            'Alkes Nonelektromedik Non Steril',
            'Alkes Diagnostik In Vitro',
            'Alat Laboratorium',
            'Consumable Medis',
        ] as $c) {
            $cats[$c] = Category::firstOrCreate(['name' => $c]);
        }

        // ---------- Produk contoh ----------
        $products = [
            ['Masker Bedah 3 Ply (Box 50)', 'Consumable Medis', 'box', 22000, 35000, 300, 50],
            ['Sarung Tangan Latex Steril (Box 100)', 'Alkes Nonelektromedik Steril', 'box', 65000, 95000, 150, 30],
            ['Spuit / Syringe 3ml (Box 100)', 'Alkes Nonelektromedik Steril', 'box', 48000, 72000, 120, 25],
            ['Infus Set Dewasa', 'Alkes Nonelektromedik Steril', 'pcs', 4500, 8000, 500, 100],
            ['Kasa Steril 16x16 (Pack)', 'Alkes Nonelektromedik Steril', 'pack', 3500, 6500, 400, 80],
            ['Rapid Test Antigen (Box 25)', 'Alkes Diagnostik In Vitro', 'box', 210000, 320000, 40, 10],
            ['Tabung Vacutainer EDTA (Box 100)', 'Alkes Diagnostik In Vitro', 'box', 85000, 130000, 60, 15],
            ['Alkohol Swab (Box 100)', 'Consumable Medis', 'box', 9000, 16000, 200, 40],
            ['Termometer Digital', 'Alat Laboratorium', 'unit', 28000, 55000, 35, 10],
            ['Pinset Anatomis Stainless', 'Alkes Nonelektromedik Non Steril', 'pcs', 15000, 30000, 20, 5],
        ];

        $i = 1;
        foreach ($products as [$name, $cat, $unit, $cost, $sell, $stock, $min]) {
            Product::firstOrCreate(
                ['code' => 'PRD-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'category_id' => $cats[$cat]->id,
                    'unit_id' => $units[$unit]->id,
                    'cost_price' => $cost,
                    'sell_price' => $sell,
                    'stock' => $stock,
                    'min_stock' => $min,
                    'is_active' => true,
                ]
            );
            $i++;
        }

        // ---------- Pelanggan ----------
        $customers = [
            ['RS Medika Sentosa', 'dr. Andi', '021-5551001', '01.234.567.8-091.000', 30],
            ['Klinik Sehat Bersama', 'Ibu Rina', '021-5552002', '02.345.678.9-091.000', 14],
            ['Apotek Kimia Farmasi', 'Bpk. Joko', '0812-1111-2222', '', 7],
            ['Puskesmas Serpong', 'Ibu Sari', '021-5553003', '', 30],
        ];
        $i = 1;
        foreach ($customers as [$name, $pic, $phone, $npwp, $term]) {
            Customer::firstOrCreate(
                ['code' => 'CUST-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                ['name' => $name, 'contact_person' => $pic, 'phone' => $phone, 'npwp' => $npwp, 'payment_term_days' => $term, 'is_active' => true]
            );
            $i++;
        }

        // ---------- Supplier ----------
        $suppliers = [
            ['PT Onemed Distribusi', 'Sales Onemed', '021-7770001', '03.111.222.3-093.000', 30],
            ['PT Enseval Medika Prima', 'Sales Enseval', '021-7770002', '04.222.333.4-093.000', 45],
            ['CV Sumber Medis Jaya', 'Bpk. Hendra', '0813-3333-4444', '', 14],
        ];
        $i = 1;
        foreach ($suppliers as [$name, $pic, $phone, $npwp, $term]) {
            Supplier::firstOrCreate(
                ['code' => 'SUP-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                ['name' => $name, 'contact_person' => $pic, 'phone' => $phone, 'npwp' => $npwp, 'payment_term_days' => $term, 'is_active' => true]
            );
            $i++;
        }
    }
}
