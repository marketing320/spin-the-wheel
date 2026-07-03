@props(['rarity' => 'common'])
@php
    $styles = [
        'common' => 'bg-slate-100 text-slate-700 ring-slate-300',
        'uncommon' => 'bg-emerald-100 text-emerald-700 ring-emerald-300',
        'rare' => 'bg-sky-100 text-sky-700 ring-sky-300',
        'epic' => 'bg-violet-100 text-violet-700 ring-violet-300',
        'legendary' => 'bg-amber-100 text-amber-800 ring-amber-400',
    ];
    $cls = $styles[$rarity] ?? $styles['common'];
@endphp
<span {{ $attributes->merge(['class' => "pill font-display uppercase ring-2 $cls"]) }}>{{ ucfirst($rarity) }}</span>
