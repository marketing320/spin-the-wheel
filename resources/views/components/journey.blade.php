@props(['current' => 1])
@php
    $steps = ['Email', 'Verify', 'Profile', 'Spin'];
@endphp
<ol class="mb-8 flex items-center justify-center gap-2 text-xs font-bold sm:gap-3">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $done = $n < $current; $active = $n === $current; @endphp
        <li class="flex items-center gap-2">
            <span @class([
                'grid h-8 w-8 place-items-center rounded-lg border-2 font-display text-[12px] transition',
                'border-slate-900 bg-brand-500 text-white pixel-shadow' => $active,
                'border-emerald-600 bg-emerald-500 text-white' => $done,
                'border-slate-300 bg-white text-slate-400' => ! $active && ! $done,
            ])>@if ($done)<i data-lucide="check" class="h-3.5 w-3.5"></i>@else{{ $n }}@endif</span>
            <span @class([
                'hidden font-display sm:inline',
                'text-slate-900' => $active,
                'text-slate-400' => ! $active,
            ])>{{ $label }}</span>
            @unless ($loop->last)
                <span class="mx-1 h-1 w-5 rounded-full bg-slate-200 sm:w-8"></span>
            @endunless
        </li>
    @endforeach
</ol>
