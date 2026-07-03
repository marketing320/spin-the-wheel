@props(['rarity' => 'common'])
@php
    $styles = [
        'common' => 'bg-slate-500/20 text-slate-300 ring-slate-400/30',
        'uncommon' => 'bg-emerald-500/20 text-emerald-300 ring-emerald-400/30',
        'rare' => 'bg-sky-500/20 text-sky-300 ring-sky-400/30',
        'epic' => 'bg-fuchsia-500/20 text-fuchsia-300 ring-fuchsia-400/30',
        'legendary' => 'bg-amber-500/20 text-amber-300 ring-amber-400/40',
    ];
    $cls = $styles[$rarity] ?? $styles['common'];
@endphp
<span {{ $attributes->merge(['class' => "pill ring-1 $cls"]) }}>{{ ucfirst($rarity) }}</span>
