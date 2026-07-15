<x-layouts.screen title="Roadshow Live" js-entry="resources/js/spin/live-view.js">
    @php
        $config = [
            'segments' => $segments,
            'settings' => $settings,
            'queue' => $queue,
            'soundUrl' => asset('sfx/spinning-wheel2.mp3'),
            'routes' => ['active' => route('live-view.active')],
        ];

        // Flat, no-gradient accent palette the CTA banner's icon/accent bar
        // can be colored with (kept in sync with LiveViewSettings::CTA_COLORS,
        // app.css's --color-*-500 custom properties, and live-view.blade.php).
        $ctaAccentHex = [
            'sun' => '#f6c31c',
            'grass' => '#24b26b',
            'grape' => '#7b5cff',
            'tangerine' => '#f5822b',
            'aqua' => '#12a5b0',
            'bubble' => '#e84f97',
        ][$settings['cta_color'] ?? 'sun'] ?? '#f6c31c';
    @endphp
    <script type="application/json" id="live-config">{!! json_encode($config) !!}</script>

    {{-- Portrait layout for LED roadshow panels: a single 344px-wide strip,
         or two side-by-side making 688px wide (height fixed at 1024px either
         way). Everything is stacked in one vertical column and sized with
         vw-relative/clamp() units so the exact same markup fluidly scales
         between both panel counts — no query params or JS branching needed.

         This shares the #live-config payload, every element ID, and the
         realtime sync logic in resources/js/spin/live-view.js with
         /live-view (see LiveViewController::sharedData()) — only this
         markup/CSS is portrait-specific. --}}
    <div class="flex h-screen w-screen flex-col items-center bg-white px-3 py-4">
        {{-- Branding --}}
        <div class="flex w-full shrink-0 items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-500 pixel-shadow">
                    <i data-lucide="ferris-wheel" class="h-4 w-4 text-white"></i>
                </span>
            </a>
            <div class="flex items-center gap-1.5 font-display text-[10px] font-bold text-slate-500">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cherry-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-cherry-500"></span>
                </span>
                LIVE
            </div>
        </div>

        {{-- Constant CTA banner — idle-only, same behavior as /live-view. --}}
        @if (($settings['cta_enabled'] ?? false) && filled($settings['cta_message'] ?? null))
            <div id="cta-banner" class="hidden mt-3 w-full shrink-0 items-stretch overflow-hidden rounded-xl border-[3px] border-slate-900 bg-white pixel-shadow">
                <span class="w-2 shrink-0" style="background:{{ $ctaAccentHex }}"></span>
                <div class="flex min-w-0 flex-1 items-center gap-2 px-3 py-2.5">
                    {{--<span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border-2 border-slate-900" style="background:{{ $ctaAccentHex }}">
                        <i data-lucide="megaphone" class="animate-pulse-glow h-4 w-4 text-slate-900"></i>
                    </span>--}}
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl text-5xl leading-none">
                            <lord-icon
                            src="https://cdn.lordicon.com/mhnfcfpf.json"
                            trigger="loop"
                            delay="0"
                            colors="primary:#aa59d9,secondary:#ffd646,tertiary:#0e75bc,quaternary:#eb242a"
                            style="width:250px;height:250px">
                        </lord-icon>
                    </div>
                    <span class="min-w-0 font-display font-bold uppercase leading-snug text-slate-900" style="font-size: clamp(0.7rem, 3.6vw, 1.25rem);">SPIN & WIN!<br>WIN EXCLUSIVE PRIZES WORTH UP TO RM200!</span>
                </div>
            </div>
        @endif

        {{-- Current player + live prize under the pointer --}}
        <div class="mt-4 flex w-full shrink-0 flex-col items-center gap-2 text-center">
            <div id="current-player" class="min-h-[1em] font-display font-bold text-brand-600" style="font-size: clamp(0.75rem, 3.4vw, 1.25rem);"></div>
            <div id="pointer-prize" class="flex w-full items-center gap-2 rounded-xl border-[3px] border-slate-900 bg-white px-3 py-2.5 pixel-shadow-lg">
                <span id="pointer-prize-chip" class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg border-2 border-slate-900" style="background:#0e75bc">
                    <img id="pointer-prize-image" src="" alt="" class="hidden h-full w-full object-cover">
                    <i id="pointer-prize-icon" data-lucide="gift" class="h-5 w-5 text-white"></i>
                </span>
                <span id="pointer-prize-name" class="min-w-0 font-display font-bold leading-tight text-slate-900" style="font-size: clamp(0.9rem, 4.2vw, 1.75rem);">—</span>
            </div>
        </div>

        {{-- Wheel — width-driven (vertical space is abundant in portrait mode,
             width is the binding constraint on both a single and double panel). --}}
        <div class="relative isolate mt-8 aspect-square w-[min(88vw,720px)] shrink-0">
            <div id="live-wheel-pointer" class="absolute left-1/2 top-[-28.3px] z-30 -translate-x-1/2 origin-top">
                <div class="h-0 w-0 border-l-[16px] border-r-[16px] border-t-[28px] border-l-transparent border-r-transparent border-t-cherry-500 drop-shadow-lg"></div>
            </div>
            <div id="wheel-stage" class="absolute inset-0 z-0 overflow-hidden rounded-full"></div>
            <img src="{{ asset('img/ring_frame.png') }}" alt="" aria-hidden="true" draggable="false"
                 class="pointer-events-none absolute inset-0 z-10 h-full w-full select-none object-contain pixelated">
            <div class="pointer-events-none absolute left-1/2 top-1/2 z-20 grid h-16 w-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow-lg">
                <img src="{{ asset('logo-black.png') }}" alt="Logo" class="h-12 w-12 object-contain">
            </div>
        </div>

        {{-- Queue — full-width block instead of /live-view's floating aside;
             the list itself still self-scrolls so a long queue can never push
             the wheel off-panel. --}}
        <div class="mt-5 w-full min-h-0 flex-1 rounded-xl border-[3px] border-slate-900 bg-white p-3 pixel-shadow">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xs font-bold text-slate-900">Queue</h2>
                <span id="queue-count" class="pill bg-brand-100 text-brand-700">{{ $queue['count'] }}</span>
            </div>
            <ol id="queue-list" class="mt-2 max-h-40 space-y-1.5 overflow-y-auto pr-1 text-xs">
                @forelse ($queue['players'] as $queuedPlayer)
                    <li class="flex items-center gap-2 rounded-lg bg-slate-100 px-2.5 py-1.5">
                        <span class="font-display text-[10px] text-brand-600">#{{ $queuedPlayer['position'] }}</span>
                        <span class="min-w-0 truncate font-semibold text-slate-700">{{ $queuedPlayer['name'] }}</span>
                    </li>
                @empty
                    <li class="text-slate-400">No players waiting</li>
                @endforelse
            </ol>
        </div>

        {{-- Prize reveal overlay (bold flat red winner screen) --}}
        <div id="prize-reveal" class="fixed inset-0 z-40 hidden flex-col items-center justify-center bg-cherry-500 px-4">
            <div class="text-center">
                <div class="font-display text-xs font-bold uppercase tracking-[0.3em] text-sun-500">Winner</div>
                <img id="reveal-prize-image" src="" alt="" class="mx-auto mt-4 hidden h-24 w-24 rounded-2xl border-4 border-white object-cover">
                <div id="reveal-prize-name" class="mt-3 font-display font-bold leading-tight text-white drop-shadow-lg" style="font-size: clamp(1.5rem, 9vw, 3rem);">—</div>
                <div id="reveal-rarity" class="mt-3 inline-block rounded-full bg-white/20 px-3 py-1.5 font-display text-xs font-bold uppercase tracking-widest text-white ring-2 ring-white/50"></div>
            </div>
        </div>
    </div>
</x-layouts.screen>
