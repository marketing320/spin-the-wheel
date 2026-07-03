<x-layouts.screen title="Live View" js-entry="resources/js/spin/live-view.js">
    @php
        $config = [
            'segments' => $segments,
            'settings' => $settings,
            'routes' => ['active' => route('live-view.active')],
        ];
    @endphp
    <script type="application/json" id="live-config">{!! json_encode($config) !!}</script>

    <div class="relative flex h-screen w-screen flex-col items-center justify-center">
        {{-- Branding --}}
        <div class="absolute left-0 right-0 top-0 flex items-center justify-between px-8 py-6">
            <div class="flex items-center gap-3 text-2xl font-black text-white">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-pink-500 shadow-lg">🎡</span>
                {{ $settings['branding'] ?? config('app.name') }}
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-400">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                </span>
                LIVE
            </div>
        </div>

        {{-- Current player --}}
        <div class="absolute top-24 text-center">
            <div id="current-player" class="text-xl font-semibold text-brand-200"></div>
        </div>

        {{-- Wheel --}}
        <div class="relative aspect-square w-[min(70vh,70vw)]">
            <div class="absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-2">
                <div class="h-0 w-0 border-l-[22px] border-r-[22px] border-t-[38px] border-l-transparent border-r-transparent border-t-white drop-shadow-2xl"></div>
            </div>
            <div id="wheel-stage" class="h-full w-full"></div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-slate-900 text-4xl shadow-2xl ring-8 ring-white/10">🎡</div>
        </div>

        {{-- Idle overlay --}}
        <div id="idle-screen" class="absolute inset-0 z-30 flex flex-col items-center justify-center bg-slate-950/70 backdrop-blur-sm">
            <div class="animate-float-slow text-7xl">🎡</div>
            <h1 class="mt-6 text-4xl font-black text-white">{{ $settings['branding'] ?? config('app.name') }}</h1>
            <p class="mt-3 text-xl text-slate-300">{{ $settings['idle_message'] ?? 'Waiting for the next lucky player…' }}</p>
        </div>

        {{-- Prize reveal overlay --}}
        <div id="prize-reveal" class="absolute inset-0 z-40 hidden flex-col items-center justify-center bg-black/80 backdrop-blur">
            <div class="text-center">
                <div class="text-sm font-bold uppercase tracking-[0.4em] text-amber-300">Winner</div>
                <div id="reveal-prize-name" class="mt-4 text-6xl font-black text-white drop-shadow-2xl">—</div>
                <div id="reveal-rarity" class="mt-5 inline-block rounded-full bg-white/10 px-5 py-2 text-lg font-semibold uppercase tracking-widest text-amber-200 ring-1 ring-white/20"></div>
            </div>
        </div>
    </div>
</x-layouts.screen>
