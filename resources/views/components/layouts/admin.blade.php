@props(['title' => 'Dashboard'])
@php
    $nav = [
        ['admin.dashboard', 'Dashboard', 'layout-dashboard'],
        ['admin.campaigns', 'Campaigns', 'target'],
        ['admin.prizes', 'Prizes', 'gift'],
        ['admin.wheel', 'Wheel Design', 'ferris-wheel'],
        ['admin.play-rules', 'Play Rules', 'repeat'],
        ['admin.forms', 'Form Builder', 'clipboard-list'],
        ['admin.geofence', 'Geofence', 'map-pin'],
        ['admin.live-view', 'Live View', 'tv'],
        ['admin.spins', 'Spin History', 'history'],
        ['admin.players', 'Players', 'users'],
        ['admin.settings', 'Settings', 'settings'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Admin · {{ config('app.name') }}</title>
    <x-head-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<div x-data="{ open: false }" class="flex min-h-screen bg-slate-50">
    {{-- Sidebar --}}
    <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 transform border-r-2 border-slate-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 px-5 font-display text-lg font-bold text-slate-900">
            <span class="grid h-8 w-8 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-500"><i data-lucide="ferris-wheel" class="h-4 w-4 text-white"></i></span>
            Admin
        </div>
        <nav class="mt-2 space-y-1 px-3">
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                    'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => request()->routeIs($route),
                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($route),
                ])>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i> {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Backdrop for mobile --}}
    <div x-show="open" @click="open = false" x-cloak class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    {{-- Content --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b-2 border-slate-200 bg-white px-5">
            <div class="flex items-center gap-3">
                <button @click="open = !open" class="lg:hidden text-slate-600"><i data-lucide="menu" class="h-6 w-6"></i></button>
                <h1 class="font-display text-lg font-bold text-slate-900">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1 hover:text-slate-900">View site <i data-lucide="external-link" class="h-3.5 w-3.5"></i></a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn-ghost !px-3 !py-1.5 text-xs">Sign out</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <div class="mx-5 mt-4 rounded-xl border-2 border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <main class="flex-1 p-5">
            {{ $slot }}
        </main>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
<x-lucide-scripts />
</body>
</html>
