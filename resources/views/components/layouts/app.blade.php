@props(['title' => null, 'jsEntry' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? \App\Support\Settings::get('branding.app_name', config('app.name')) }}</title>
    <x-head-fonts />
    @vite(array_merge(['resources/css/app.css'], (array) ($jsEntry ?? 'resources/js/app.js')))
    @stack('head')
</head>
<body class="antialiased">
    <div class="relative flex min-h-screen flex-col">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-display text-lg font-bold tracking-tight text-slate-900">
                <span class="grid h-9 w-9 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-500 pixel-shadow"><i data-lucide="ferris-wheel" class="h-5 w-5 text-white"></i></span>
                {{ \App\Support\Settings::get('branding.app_name', config('app.name')) }}
            </a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('live-view') }}" target="_blank" class="btn-ghost !px-3 !py-2 text-xs">
                    <i data-lucide="tv" class="h-4 w-4"></i> Live View
                </a>
                @auth('player')
                    <form method="POST" action="{{ route('player.logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost !px-3 !py-2 text-xs">Sign out</button>
                    </form>
                @endauth
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center px-5 py-6">
            {{ $slot }}
        </main>

        <footer class="mx-auto w-full max-w-5xl px-5 py-6 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} {{ \App\Support\Settings::get('branding.app_name', config('app.name')) }}. All rights reserved.
        </footer>
    </div>
    <x-lucide-scripts />
    @stack('scripts')
</body>
</html>
