@props(['title' => null, 'jsEntry' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <x-head-fonts />
    {{-- Livewire only auto-injects its JS (which bundles Alpine) on pages
         that render an actual Livewire component. Several pages under this
         layout (landing, /spin, /result) are plain controllers with no
         Livewire component, so without this explicit directive, Alpine
         never loads there and any x-data/@click/x-show markup is inert. --}}
    @livewireStyles
    <script src="{{ asset('js/confettea.min.js') }}?v={{ @filemtime(public_path('js/confettea.min.js')) ?: '1' }}"></script>
    @vite(array_merge(['resources/css/app.css'], (array) ($jsEntry ?? 'resources/js/app.js')))
    @stack('head')
</head>
<body class="player-surface antialiased" x-data="{ mobileNavOpen: false }">
    <div class="relative flex min-h-screen flex-col">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-4 sm:px-5 sm:py-5">
            <a href="{{ route('home') }}" class="group flex origin-left items-center transition-transform duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] active:translate-x-0.5 active:translate-y-0.5 active:scale-[0.98]">
                <span class="flex h-10 items-center rounded-lg border-2 border-slate-900 bg-white px-2.5 pixel-shadow transition-all duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] group-active:translate-x-0.5 group-active:translate-y-0.5 group-active:scale-95 group-active:shadow-[2px_2px_0_0_#0f172a]">
                    <img src="{{ asset('logo-black.png') }}" alt="{{ config('app.name') }}" class="h-6 w-auto object-contain">
                </span>
            </a>

            {{-- Desktop nav — unchanged inline links. --}}
            <nav class="hidden items-center gap-2 text-sm sm:flex">
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

            {{-- Mobile hamburger trigger — opens the right-side drawer below. --}}
            @auth('player')
                <button type="button" @click="mobileNavOpen = true" class="btn-ghost !p-2.5 sm:hidden" aria-label="Open menu">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
            @endauth
        </header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center px-3 py-4 sm:px-5 sm:py-6">
            {{ $slot }}
        </main>

        <footer class="mx-auto w-full max-w-5xl px-4 py-4 text-center text-xs text-slate-500 sm:px-5 sm:py-6">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </footer>
    </div>

    {{-- Mobile nav drawer (My Prizes / Logout), slides in from the right. --}}
    @auth('player')
        <div x-show="mobileNavOpen" x-cloak x-transition.opacity
             @click="mobileNavOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/40 sm:hidden"></div>

        <aside :class="mobileNavOpen ? 'translate-x-0' : 'translate-x-full'"
               class="fixed inset-y-0 right-0 z-50 w-64 max-w-[80vw] transform border-l-2 border-slate-900 bg-white p-5 shadow-xl transition-transform duration-200 ease-out sm:hidden">
            <div class="flex items-center justify-between">
                <span class="font-display text-sm font-bold text-slate-900">Menu</span>
                <button type="button" @click="mobileNavOpen = false" aria-label="Close menu" class="text-slate-500 hover:text-slate-900">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <nav class="mt-6 flex flex-col gap-2">
                <a href="{{ route('player.prizes') }}" wire:navigate @click="mobileNavOpen = false" class="btn-ghost w-full justify-start !py-2.5 text-sm">
                    <i data-lucide="gift" class="h-4 w-4"></i> My Prizes
                </a>
                <form method="POST" action="{{ route('player.logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="btn-ghost w-full justify-start !py-2.5 text-sm"><i data-lucide="log-out" class="h-4 w-4"></i> Logout</button>
                </form>
            </nav>
        </aside>
    @endauth

    <style>[x-cloak]{display:none!important}</style>
    <x-lucide-scripts />
    @stack('scripts')
    @livewireScripts
</body>
</html>
