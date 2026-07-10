@props(['title', 'subtitle' => null])

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-display text-xl font-bold text-navy">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        <div class="flex items-center gap-2">{{ $action }}</div>
    @endisset
</div>
