@props(['current' => 1])
@php
    $steps = ['Email', 'Verify', 'Profile', 'Spin'];
@endphp
<ol class="mb-8 flex items-center justify-center gap-2 text-xs font-bold sm:gap-3">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $done = $n < $current; $active = $n === $current; @endphp
        <li class="flex items-center gap-2" @if ($active) aria-current="step" @endif>
            <span @class([
                'grid h-8 w-8 place-items-center rounded-md border font-display text-xs',
                'border-transparent bg-[var(--event-green)] text-[var(--event-ink)]' => $active,
                'event-icon border-transparent' => $done,
                'border-[var(--event-border)] text-slate-400' => ! $active && ! $done,
            ])>@if ($done)<i data-lucide="check" class="h-3.5 w-3.5"></i>@else{{ $n }}@endif</span>
            <span @class([
                'sr-only font-display sm:not-sr-only',
                'text-slate-900' => $active,
                'text-slate-400' => ! $active,
            ])>{{ $label }}</span>
            @unless ($loop->last)
                <span class="mx-1 h-px w-5 bg-[var(--event-border)] sm:w-8" aria-hidden="true"></span>
            @endunless
        </li>
    @endforeach
</ol>
