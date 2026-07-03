<x-layouts.screen title="Live View" js-entry="resources/js/spin/live-view.js">
    @php
        $config = [
            'segments' => $segments,
            'settings' => $settings,
            'queue' => $queue,
            'soundUrl' => asset('sfx/spinning-wheel2.mp3'),
            'routes' => ['active' => route('live-view.active')],
        ];
    @endphp
    <script type="application/json" id="live-config">{!! json_encode($config) !!}</script>

    <div class="relative flex h-screen w-screen flex-col items-center justify-center bg-white">
        {{-- Branding --}}
        <div class="absolute left-0 right-0 top-0 flex items-center justify-between px-8 py-6">
            <div class="flex items-center gap-3 font-display text-2xl font-bold text-slate-900">
                <span class="grid h-11 w-11 place-items-center rounded-2xl border-2 border-slate-900 bg-cherry-500 pixel-shadow">
                    <img src="{{ asset('logo-black.png') }}" alt="Logo" class="h-15 w-15 object-contain">
                </span>
                
            </div>
            <div class="flex items-center gap-2 font-display text-sm font-bold text-slate-500">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cherry-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-cherry-500"></span>
                </span>
                LIVE
            </div>
        </div>

        {{-- Current player + live prize under the pointer --}}
        <div class="absolute top-20 z-30 flex flex-col items-center gap-3 text-center">
            <div id="current-player" class="font-display text-xl font-bold text-brand-600"></div>
            <div id="pointer-prize" class="flex items-center gap-4 rounded-2xl border-[3px] border-slate-900 bg-white px-6 py-4 pixel-shadow-lg">
                <span id="pointer-prize-chip" class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border-2 border-slate-900" style="background:#0e75bc">
                    <img id="pointer-prize-image" src="" alt="" class="hidden h-full w-full object-cover">
                    <i id="pointer-prize-icon" data-lucide="gift" class="h-8 w-8 text-white"></i>
                </span>
                <span id="pointer-prize-name" class="font-display text-3xl font-bold leading-tight text-slate-900">—</span>
            </div>
        </div>

        {{-- Wheel --}}
        <div class="relative aspect-square w-[min(80vh,72vw)]">
            <div id="live-wheel-pointer" class="absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-2 origin-top">
                <div class="h-0 w-0 border-l-[22px] border-r-[22px] border-t-[38px] border-l-transparent border-r-transparent border-t-cherry-500 drop-shadow-lg"></div>
            </div>
            <div id="wheel-stage" class="h-full w-full"></div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow-lg"><i data-lucide="ferris-wheel" class="h-10 w-10 text-cherry-500"></i></div>
        </div>

        <aside class="absolute bottom-8 right-8 z-30 w-80 rounded-2xl border-[3px] border-slate-900 bg-white p-5 pixel-shadow">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-sm font-bold text-slate-900">Queue</h2>
                <span id="queue-count" class="pill bg-brand-100 text-brand-700">{{ $queue['count'] }}</span>
            </div>
            <ol id="queue-list" class="mt-4 max-h-[13.25rem] space-y-2 overflow-y-auto pr-1 text-sm">
                @forelse ($queue['players'] as $queuedPlayer)
                    <li class="flex items-center gap-3 rounded-lg bg-slate-100 px-3 py-2">
                        <span class="font-display text-xs text-brand-600">#{{ $queuedPlayer['position'] }}</span>
                        <span class="font-semibold text-slate-700">{{ $queuedPlayer['name'] }}</span>
                    </li>
                @empty
                    <li class="text-slate-400">No players waiting</li>
                @endforelse
            </ol>
        </aside>

        <div id="enable-sound-overlay" class="absolute inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-6 backdrop-blur-sm">
            <button id="enable-sound-button" type="button" class="btn-primary text-lg">
                <i data-lucide="volume-2" class="h-6 w-6"></i>
                ENABLE SOUND
            </button>
        </div>

        {{-- Idle overlay --}}
        <div id="idle-screen" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 backdrop-blur-sm">
                <img src="{{ asset('logo-black.png') }}" alt="Logo" class="animate-float-slow text-brand-500 object-contain" style="height: 10rem; width: 10rem;">
            <h1 class="mt-6 font-display text-4xl font-bold text-slate-900">{{ $settings['branding'] ?? config('app.name') }}</h1>
            <p class="mt-3 text-xl font-semibold text-slate-600">{{ $settings['idle_message'] ?? 'Waiting for the next lucky player…' }}</p>
        </div>

        {{-- Prize reveal overlay (bold flat red winner screen) --}}
        <div id="prize-reveal" class="absolute inset-0 z-40 hidden flex-col items-center justify-center bg-cherry-500">
            <div class="text-center">
                <div class="font-display text-sm font-bold uppercase tracking-[0.4em] text-sun-500">Winner</div>
                <img id="reveal-prize-image" src="" alt="" class="mx-auto mt-6 hidden h-40 w-40 rounded-3xl border-4 border-white object-cover">
                <div id="reveal-prize-name" class="mt-4 font-display text-6xl font-bold text-white drop-shadow-lg">—</div>
                <div id="reveal-rarity" class="mt-5 inline-block rounded-full bg-white/20 px-5 py-2 font-display text-lg font-bold uppercase tracking-widest text-white ring-2 ring-white/50"></div>
            </div>
        </div>
    </div>
</x-layouts.screen>
