@props(['title', 'subtitle' => null])
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-xl font-bold text-white">{{ $title }}</h2>
        @if ($subtitle)<p class="mt-0.5 text-sm text-slate-400">{{ $subtitle }}</p>@endif
    </div>
    <div class="flex items-center gap-2">{{ $slot }}</div>
</div>
