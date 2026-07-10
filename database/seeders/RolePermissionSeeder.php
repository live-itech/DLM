<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Daftar permission granular per modul.
     * Konvensi: "<modul>.<aksi>"  (view, create, update, delete)
     */
    public const PERMISSIONS = [
        // Master data
        'products.view', 'products.create', 'products.update', 'products.delete',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'units.view', 'units.create', 'units.update', 'units.delete',
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
        // Transaksi penjualan
        'sales-orders.view', 'sales-orders.create', 'sales-orders.update', 'sales-orders.delete',
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
        // Transaksi pembelian
        'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.update', 'purchase-orders.delete',
        // Pajak & penawaran
        'tax-invoices.view', 'tax-invoices.create', 'tax-invoices.update', 'tax-invoices.delete',
        'quotations.view', 'quotations.create', 'quotations.update', 'quotations.delete',
        // Laporan
        'reports.operational',   // stok, penjualan (boleh staff)
        'reports.finance',       // margin, modal, laba-rugi (admin only)
        // Sistem
        'settings.manage',
        'users.manage',
        'transactions.void',     // batalkan/void transaksi final (admin only)
    ];

    /**
     * Permission yang BOLEH diakses Staff (subset).
     * Sisanya (finance, settings, users, void, delete) khusus Admin.
     */
    public const STAFF_PERMISSIONS = [
        'products.view', 'products.create', 'products.update',
        'categories.view', 'categories.create', 'categories.update',
        'units.view', 'units.create', 'units.update',
        'customers.view', 'customers.create', 'customers.update',
        'suppliers.view', 'suppliers.create', 'suppliers.update',
        'sales-orders.view', 'sales-orders.create', 'sales-orders.update',
        'invoices.view', 'invoices.create', 'invoices.update',
        'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.update',
        'tax-invoices.view', 'tax-invoices.create',
        'quotations.view', 'quotations.create', 'quotations.update',
        'reports.operational',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Admin: semua permission
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(self::PERMISSIONS);

        // Staff: subset operasional
        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staff->syncPermissions(self::STAFF_PERMISSIONS);
    }
}
