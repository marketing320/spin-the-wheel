@props(['title' => 'Dashboard'])
@php
    $nav = [
        ['admin.dashboard', 'Dashboard', '📊'],
        ['admin.campaigns', 'Campaigns', '🎯'],
        ['admin.prizes', 'Prizes', '🎁'],
        ['admin.wheel', 'Wheel Design', '🎡'],
        ['admin.play-rules', 'Play Rules', '🔁'],
        ['admin.forms', 'Form Builder', '📝'],
        ['admin.geofence', 'Geofence', '📍'],
        ['admin.live-view', 'Live View', '📺'],
        ['admin.spins', 'Spin History', '🕘'],
        ['admin.players', 'Players', '👥'],
        ['admin.settings', 'Settings', '⚙️'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Admin · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<div x-data="{ open: false }" class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-white/10 bg-slate-950/80 backdrop-blur transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 px-5 text-lg font-extrabold text-white">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-pink-500">🎡</span>
            Admin
        </div>
        <nav class="mt-2 space-y-1 px-3">
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                    'bg-brand-500/20 text-white ring-1 ring-brand-400/30' => request()->routeIs($route),
                    'text-slate-400 hover:bg-white/5 hover:text-white' => ! request()->routeIs($route),
                ])>
                    <span class="text-base">{{ $icon }}</span> {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Backdrop for mobile --}}
    <div x-show="open" @click="open = false" x-cloak class="fixed inset-0 z-30 bg-black/60 lg:hidden"></div>

    {{-- Content --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-white/10 bg-slate-950/60 px-5 backdrop-blur">
            <div class="flex items-center gap-3">
                <button @click="open = !open" class="lg:hidden text-slate-300">☰</button>
                <h1 class="text-lg font-bold text-white">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-400">
                <a href="{{ route('home') }}" target="_blank" class="hover:text-white">View site ↗</a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn-ghost !px-3 !py-1.5 text-xs">Sign out</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <div class="mx-5 mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <main class="flex-1 p-5">
            {{ $slot }}
        </main>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
</body>
</html>
