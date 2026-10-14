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

            {{-- Compact mobile account menu; desktop links remain inline above. --}}
            @auth('player')
                <button type="button"
                        x-ref="mobileMenuTrigger"
                        @click="mobileNavOpen = true; $nextTick(() => $refs.mobileMenuClose.focus())"
                        :aria-expanded="mobileNavOpen.toString()"
                        aria-controls="mobile-account-menu"
                        class="btn-ghost !rounded-lg !p-2 sm:hidden"
                        aria-label="Open account menu">
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

    {{-- Compact mobile account menu. --}}
    @auth('player')
        <div x-show="mobileNavOpen" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileNavOpen = false"
             @keydown.escape.window="mobileNavOpen = false; $refs.mobileMenuTrigger.focus()"
             class="fixed inset-0 z-40 bg-slate-900/15 backdrop-blur-[1px] sm:hidden"></div>

        <aside id="mobile-account-menu"
               x-show="mobileNavOpen"
               x-cloak
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
               x-transition:enter-end="opacity-100 scale-100 translate-y-0"
               x-transition:leave="transition ease-in duration-100"
               x-transition:leave-start="opacity-100 scale-100 translate-y-0"
               x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
               @keydown.escape.window="mobileNavOpen = false; $refs.mobileMenuTrigger.focus()"
               class="fixed right-4 top-[4.75rem] z-50 w-64 max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl border-[3px] border-slate-900 bg-white p-3 shadow-[5px_5px_0_0_#0f172a] sm:hidden">
            <div class="mb-2 flex items-center justify-between px-1">
                <div>
                    <span class="block font-display text-xs font-bold text-slate-900">Your account</span>
                </div>
                <button type="button"
                        x-ref="mobileMenuClose"
                        @click="mobileNavOpen = false; $refs.mobileMenuTrigger.focus()"
                        aria-label="Close menu"
                        class="grid h-9 w-9 place-items-center rounded-lg border-2 border-transparent text-slate-500 transition hover:border-slate-900 hover:bg-slate-100 hover:text-slate-900 focus:border-slate-900 focus:outline-none">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <nav aria-label="Account menu" class="flex flex-col gap-2">
                <a href="{{ route('player.prizes') }}"
                   wire:navigate
                   @click="mobileNavOpen = false"
                   class="group flex items-center gap-3 rounded-xl border-2 border-slate-200 px-3 py-2.5 text-slate-900 transition hover:-translate-y-0.5 hover:border-brand-500 hover:bg-brand-50 focus:border-brand-500 focus:outline-none">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border-2 border-slate-900 bg-sun-500 text-slate-900 transition group-hover:rotate-[-6deg]">
                        <i data-lucide="gift" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-display text-[0.65rem] font-bold">My Prizes</span>
                        <span class="mt-1 block text-xs text-slate-500">See your rewards</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('player.logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                            @click="mobileNavOpen = false"
                            class="group flex w-full items-center gap-3 rounded-xl border-2 border-slate-200 px-3 py-2.5 text-left text-slate-900 transition hover:-translate-y-0.5 hover:border-cherry-400 hover:bg-cherry-50 focus:border-cherry-400 focus:outline-none">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-100 text-cherry-600 transition group-hover:rotate-[-6deg]">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-display text-[0.65rem] font-bold">Log out</span>
                            <span class="mt-1 block text-xs text-slate-500">End your session</span>
                        </span>
                    </button>
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
