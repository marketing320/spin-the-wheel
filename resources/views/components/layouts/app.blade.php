@props(['title' => null, 'jsEntry' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <x-head-fonts />
    <script src="{{ asset('js/confettea.min.js') }}?v={{ @filemtime(public_path('js/confettea.min.js')) ?: '1' }}"></script>
    @vite(array_merge(['resources/css/app.css'], (array) ($jsEntry ?? 'resources/js/app.js')))
    @stack('head')
</head>
<body class="player-surface antialiased">
    <div class="relative flex min-h-screen flex-col">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-4 sm:px-5 sm:py-5">
            <a href="{{ route('home') }}" class="group flex origin-left items-center gap-2 font-display text-lg font-bold tracking-tight text-slate-900 transition-transform duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] active:translate-x-0.5 active:translate-y-0.5 active:scale-[0.98]">
                <span class="grid h-9 w-9 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-500 pixel-shadow transition-all duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] group-active:translate-x-0.5 group-active:translate-y-0.5 group-active:scale-95 group-active:shadow-[2px_2px_0_0_#0f172a]"><i data-lucide="ferris-wheel" class="h-5 w-5 text-white transition-transform duration-200 ease-[cubic-bezier(.34,1.56,.64,1)] group-active:rotate-[-20deg] group-active:scale-90"></i></span>
            </a>
            <nav class="flex items-center gap-2 text-sm">
                {{--<a href="{{ route('live-view') }}" target="_blank" class="btn-ghost !px-3 !py-2 text-xs">
                    <i data-lucide="tv" class="h-4 w-4"></i> Live View
                </a>--}}
                @auth('player')
                    <a href="{{ route('player.prizes') }}" wire:navigate class="btn-ghost !px-3 !py-2 text-xs">
                        <i data-lucide="gift" class="h-4 w-4"></i> My Prizes
                    </a>
                    <form method="POST" action="{{ route('player.logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost !px-3 !py-2 text-xs"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</button>
                    </form>
                @endauth
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center px-3 py-4 sm:px-5 sm:py-6">
            {{ $slot }}
        </main>

        <footer class="mx-auto w-full max-w-5xl px-4 py-4 text-center text-xs text-slate-500 sm:px-5 sm:py-6">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </footer>
    </div>
    <x-lucide-scripts />
    @stack('scripts')
</body>
</html>
