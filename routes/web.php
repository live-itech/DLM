<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaxInvoiceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureUserActive::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profil akun (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ================= MASTER DATA =================
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('units', UnitController::class)->except('show');
    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('suppliers', SupplierController::class)->except('show');

    // ================= PEMBELIAN =================
    Route::controller(PurchaseOrderController::class)->prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('{purchaseOrder}/pdf', 'pdf')->name('pdf');
        Route::post('{purchaseOrder}/order', 'order')->name('order');
        Route::post('{purchaseOrder}/cancel', 'cancel')->name('cancel');
        Route::get('{purchaseOrder}/receive', 'receiveForm')->name('receive');
        Route::post('{purchaseOrder}/receive', 'storeReceipt')->name('receipt.store');
        Route::post('{purchaseOrder}/payments', 'storePayment')->name('payments.store');
    });
    Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase-orders' => 'purchaseOrder']);

    // ================= PENJUALAN =================
    Route::controller(SalesOrderController::class)->prefix('sales-orders')->name('sales-orders.')->group(function () {
        Route::post('{salesOrder}/confirm', 'confirm')->name('confirm');
        Route::post('{salesOrder}/cancel', 'cancel')->name('cancel');
        Route::post('{salesOrder}/invoice', 'toInvoice')->name('to-invoice');
    });
    Route::resource('sales-orders', SalesOrderController::class)->parameters(['sales-orders' => 'salesOrder']);

    // ================= INVOICE / PIUTANG =================
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
    Route::patch('invoices/{invoice}/dates', [InvoiceController::class, 'updateDates'])->name('invoices.dates.update');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/tax-invoice', [TaxInvoiceController::class, 'generate'])->name('invoices.generate-tax');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'edit', 'update']);

    // ================= FAKTUR PAJAK =================
    Route::controller(TaxInvoiceController::class)->prefix('tax-invoices')->name('tax-invoices.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('input/create', 'createInput')->name('input.create');
        Route::post('input', 'storeInput')->name('input.store');
        Route::get('{taxInvoice}', 'show')->name('show');
        Route::get('{taxInvoice}/pdf', 'pdf')->name('pdf');
        Route::delete('{taxInvoice}', 'destroy')->name('destroy');
    });

    // ================= SURAT PENAWARAN =================
    Route::controller(QuotationController::class)->prefix('quotations')->name('quotations.')->group(function () {
        Route::post('{quotation}/status', 'setStatus')->name('status');
        Route::post('{quotation}/sales-order', 'toSalesOrder')->name('to-sales-order');
        Route::get('{quotation}/pdf', 'pdf')->name('pdf');
    });
    Route::resource('quotations', QuotationController::class);

    // ================= LAPORAN =================
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('sales', 'sales')->name('sales');
        Route::get('purchases', 'purchases')->name('purchases');
        Route::get('stock', 'stock')->name('stock');
        Route::get('stock/{product}/card', 'stockCard')->name('stock-card');
        Route::get('receivables', 'receivables')->name('receivables');
        Route::get('payables', 'payables')->name('payables');
        Route::get('profit-loss', 'profitLoss')->name('profit-loss');
        Route::get('tax-recap', 'taxRecap')->name('tax-recap');
    });

    // ================= PENGATURAN (Admin) =================
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/company', [SettingController::class, 'updateCompany'])->name('settings.company');
        Route::put('/settings/tax', [SettingController::class, 'updateTax'])->name('settings.tax');
        Route::put('/settings/bank', [SettingController::class, 'updateBank'])->name('settings.bank');
    });

    // ================= PENGGUNA (Admin) =================
    Route::resource('users', UserController::class)->except('show');
});

require __DIR__.'/auth.php';
