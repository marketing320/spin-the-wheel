@props(['current' => 1])
@php
    $steps = ['Email', 'Verify', 'Profile', 'Spin'];
@endphp
<ol class="mb-8 flex items-center justify-center gap-2 text-xs font-medium sm:gap-3">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $done = $n < $current; $active = $n === $current; @endphp
        <li class="flex items-center gap-2">
            <span @class([
                'grid h-7 w-7 place-items-center rounded-full border text-[11px] font-bold transition',
                'border-brand-400 bg-brand-500 text-white shadow-lg shadow-brand-500/40' => $active,
                'border-emerald-400/60 bg-emerald-500/20 text-emerald-300' => $done,
                'border-white/15 bg-white/5 text-slate-500' => ! $active && ! $done,
            ])>{{ $done ? '✓' : $n }}</span>
            <span @class([
                'hidden sm:inline',
                'text-white' => $active,
                'text-slate-500' => ! $active,
            ])>{{ $label }}</span>
            @unless ($loop->last)
                <span class="mx-1 h-px w-5 bg-white/10 sm:w-8"></span>
            @endunless
        </li>
    @endforeach
</ol>
