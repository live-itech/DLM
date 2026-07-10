@props(['route', 'dates' => true, 'from' => null, 'to' => null, 'params' => []])

<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <form method="GET" action="{{ $route }}" class="flex flex-wrap items-end gap-2">
        @foreach ($params as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        @if ($dates)
            <div>
                <label class="mb-1 block text-xs text-gray-500">Dari</label>
                <input type="date" name="from" value="{{ request('from', $from?->toDateString()) }}" class="form-input">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-500">Sampai</label>
                <input type="date" name="to" value="{{ request('to', $to?->toDateString()) }}" class="form-input">
            </div>
        @endif
        {{ $extra ?? '' }}
        <button class="btn-outline">Tampilkan</button>
    </form>

    <div class="flex gap-2">
        <a href="{{ $route }}?{{ http_build_query(array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn-outline text-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Excel
        </a>
        <a href="{{ $route }}?{{ http_build_query(array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn-outline text-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h6l6 6v10a2 2 0 01-2 2z"/></svg>
            PDF
        </a>
    </div>
</div>
