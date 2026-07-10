# Aplikasi Bisnis PT Dimas Love Medika

Aplikasi bisnis internal berbasis web untuk **PT Dimas Love Medika** (distributor alat kesehatan, PKP).
Mengelola master data, transaksi pembelian & penjualan, faktur pajak, surat penawaran, laporan, dan dashboard.

## Tech Stack

- **Laravel 12** (PHP 8.5) · **MySQL 8**
- **Blade + Tailwind CSS 3 + Alpine.js** (server-rendered)
- **ApexCharts** (grafik dashboard)
- **spatie/laravel-permission** (role & hak akses) · **spatie/laravel-activitylog** (audit trail)
- **barryvdh/laravel-dompdf** (PDF) · **openspout/openspout** (export Excel .xlsx)
- Auth: **Laravel Breeze** (Blade)

## Fitur Utama

- **Role & Hak Akses**: Admin (akses penuh) & Staff (operasional; tidak melihat modal/margin/laba-rugi & pengaturan)
- **Master Data**: produk, kategori, satuan, pelanggan, supplier, gudang, pengaturan pajak & profil perusahaan
- **Pembelian**: Purchase Order → penerimaan barang (stok bertambah) → pembayaran hutang
- **Penjualan**: Sales Order → konfirmasi (stok berkurang) → invoice → pembayaran piutang (cicil)
- **PPN opsional** per transaksi (bisa dengan / tanpa PPN)
- **Faktur Pajak**: Keluaran (nomor NSFP otomatis, urut, anti-duplikat) & Masukan, PDF resmi + rekap PPN
- **Surat Penawaran (Quotation)**: PDF berbrand + konversi ke Sales Order
- **Laporan**: penjualan, pembelian, stok, kartu stok, piutang, hutang (aging), laba rugi, rekap PPN — export **Excel & PDF**
- **Dashboard**: statistik + grafik penjualan vs pembelian, produk terlaris, stok kritis
- **Audit trail** untuk perubahan data penting
- **Manajemen Pengguna** oleh Admin (registrasi publik dinonaktifkan)

## Prasyarat

- PHP 8.5 dengan ekstensi: `pdo_mysql, mbstring, gd, zip, bcmath, curl, intl, xml`
- Composer 2.x
- MySQL 8.x
- Node.js 20+ & npm

## Instalasi

```bash
# 1. Install dependency
composer install
npm install

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate
# Edit .env → sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD
#   (jika password mengandung karakter '#', bungkus dengan tanda kutip: DB_PASSWORD="pass#word")

# 3. Buat database (contoh)
#   CREATE DATABASE dlm_medika CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Migrasi + seed data awal (role, admin/staff, master data dummy)
php artisan migrate --seed

# 5. Symlink storage (untuk gambar produk)
php artisan storage:link

# 6. Build asset frontend
npm run build

# 7. Jalankan
php artisan serve
```

Akses di `http://127.0.0.1:8000` (atau port lain via `php artisan serve --port=8001`).

## Akun Default (dari seeder)

| Role  | Email                          | Password   |
|-------|--------------------------------|------------|
| Admin | `admin@dimaslovemedika.com`    | `password` |
| Staff | `staff@dimaslovemedika.com`    | `password` |

> **Ganti password default** setelah instalasi (menu Profil / Pengguna).

## Pengembangan

```bash
npm run dev                         # Vite hot-reload
php artisan migrate:fresh --seed    # reset database + data awal
```

## Struktur Modul

| Modul | Isi |
|-------|-----|
| Master Data | `Product, Category, Unit, Customer, Supplier, Warehouse, Setting` |
| Transaksi | `PurchaseOrder, GoodsReceipt, SalesOrder, Invoice`, pembayaran, `StockMovement` |
| Pajak & Penawaran | `TaxInvoice, Quotation` |
| Layanan | `App\Services\StockService` (stok + locking), `App\Services\NsfpGenerator` (nomor faktur pajak) |
| Laporan | `App\Http\Controllers\ReportController` + export `App\Support\XlsxExport` |

## Catatan

- Semua transaksi finansial memakai **DB transaction** + row-locking agar konsisten.
- Nomor Faktur Pajak digenerate berurutan dengan penguncian baris (tidak loncat/duplikat).
- Profil perusahaan & tarif PPN diatur di menu **Pengaturan** (dipakai pada kop dokumen PDF).
- Isi **NPWP perusahaan** di Pengaturan agar tampil pada Faktur Pajak.

---
© PT Dimas Love Medika — Aplikasi Bisnis Internal
