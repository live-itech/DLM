@php
    // Definisi menu. `route` = nama route (null / belum ada -> tampil non-aktif).
    // `active` = pola routeIs untuk menandai menu aktif.
    $menu = [
        ['type' => 'link', 'label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home'],
        ['type' => 'header', 'label' => 'Master Data'],
        ['type' => 'link', 'label' => 'Produk',    'route' => 'products.index',   'active' => 'products.*',   'icon' => 'box'],
        ['type' => 'link', 'label' => 'Kategori',   'route' => 'categories.index', 'active' => 'categories.*', 'icon' => 'tag'],
        ['type' => 'link', 'label' => 'Satuan',     'route' => 'units.index',      'active' => 'units.*',      'icon' => 'scale'],
        ['type' => 'link', 'label' => 'Pelanggan',  'route' => 'customers.index',  'active' => 'customers.*',  'icon' => 'users'],
        ['type' => 'link', 'label' => 'Supplier',   'route' => 'suppliers.index',  'active' => 'suppliers.*',  'icon' => 'truck'],
        ['type' => 'header', 'label' => 'Penjualan'],
        ['type' => 'link', 'label' => 'Sales Order',       'route' => 'sales-orders.index', 'active' => 'sales-orders.*', 'icon' => 'cart'],
        ['type' => 'link', 'label' => 'Invoice / Faktur',  'route' => 'invoices.index',     'active' => 'invoices.*',     'icon' => 'doc'],
        ['type' => 'header', 'label' => 'Pembelian'],
        ['type' => 'link', 'label' => 'Purchase Order',    'route' => 'purchase-orders.index', 'active' => 'purchase-orders.*', 'icon' => 'inbox'],
        ['type' => 'header', 'label' => 'Penawaran'],
        ['type' => 'link', 'label' => 'Penawaran Harga',   'route' => 'quotations.index',   'active' => 'quotations.*',   'icon' => 'quote'],
        ['type' => 'header', 'label' => 'Lainnya'],
        ['type' => 'link', 'label' => 'Laporan',    'route' => 'reports.index',  'active' => 'reports.*',  'icon' => 'chart'],
        ['type' => 'link', 'label' => 'Pengguna',   'route' => 'users.index',    'active' => 'users.*',    'icon' => 'users', 'can' => 'users.manage'],
        ['type' => 'link', 'label' => 'Pengaturan', 'route' => 'settings.index', 'active' => 'settings.*', 'icon' => 'cog', 'can' => 'settings.manage'],
    ];

    // Sembunyikan item yang tidak diizinkan untuk role user.
    $menu = array_values(array_filter($menu, function ($item) {
        return empty($item['can']) || auth()->user()?->can($item['can']);
    }));

    $icons = [
        'home'   => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h4v6h3a1 1 0 001-1V10',
        'box'    => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'tag'    => 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8V4a1 1 0 011-1z',
        'scale'  => 'M3 6l3 1m0 0l-3 9a5 5 0 006 0l-3-9m0 0l6-2m6 2l3-1m-3 1l-3 9a5 5 0 006 0l-3-9m0 0l-6-2m-6 2V4m6 0v14',
        'users'  => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
        'truck'  => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM3 4h11v10H3zM14 8h4l3 3v3h-7z',
        'cart'   => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 5h12M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z',
        'doc'    => 'M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h6l6 6v10a2 2 0 01-2 2z',
        'inbox'  => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2 5H6l-2-5m16 0h-5l-1 2H9l-1-2H4',
        'receipt'=> 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-3-7 3V5a2 2 0 012-2h10a2 2 0 012 2z',
        'quote'  => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-4 4z',
        'chart'  => 'M11 3.055A9 9 0 1020.945 13H11V3.055zM20.488 9A9 9 0 0015 3.512V9h5.488z',
        'cog'    => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ];
@endphp

<aside x-cloak
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 w-64 transform bg-navy-dark text-gray-300 transition-transform duration-200 ease-in-out lg:translate-x-0">
    <div class="flex h-full flex-col">
        <!-- Logo -->
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-5">
            <img src="{{ asset('img/logo.png') }}" alt="DLM" class="h-11 w-11 shrink-0 rounded-lg bg-white/5 object-contain p-1">
            <div class="leading-tight">
                <p class="font-display text-sm font-bold tracking-wide text-gold">DIMAS LOVE</p>
                <p class="font-display text-xs font-semibold tracking-widest text-gold-light/80">MEDIKA</p>
            </div>
        </div>

        <!-- Navigasi -->
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
            @foreach ($menu as $item)
                @if ($item['type'] === 'header')
                    <p class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ $item['label'] }}</p>
                @else
                    @php
                        $exists = $item['route'] && \Illuminate\Support\Facades\Route::has($item['route']);
                        $isActive = $exists && request()->routeIs($item['active']);
                        $href = $exists ? route($item['route']) : '#';
                    @endphp
                    <a href="{{ $href }}"
                       @class([
                           'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                           'bg-gold-gradient text-navy-dark shadow-gold' => $isActive,
                           'text-gray-300 hover:bg-white/5 hover:text-gold-light' => ! $isActive,
                           'opacity-40' => ! $exists,
                       ])>
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icons[$item['icon']] ?? '' }}"/>
                        </svg>
                        <span>{{ $item['label'] }}</span>
                        @unless ($exists)
                            <span class="ml-auto text-[9px] uppercase tracking-wide text-gray-600">segera</span>
                        @endunless
                    </a>
                @endif
            @endforeach
        </nav>

        <!-- Footer sidebar -->
        <div class="border-t border-white/10 px-5 py-3 text-[11px] text-gray-500">
            <p>PT Dimas Love Medika</p>
            <p class="text-gray-600">Sistem Bisnis Internal</p>
        </div>
    </div>
</aside>
