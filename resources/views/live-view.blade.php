<x-layouts.screen title="Live View" js-entry="resources/js/spin/live-view.js">
    @php
        $config = [
            'segments' => $segments,
            'settings' => $settings,
            'queue' => $queue,
            'soundUrl' => asset('sfx/spinning-wheel2.mp3'),
            'routes' => ['active' => route('live-view.active')],
        ];

        // Flat, no-gradient accent palette the CTA banner's icon/accent bar
        // can be colored with (kept in sync with LiveViewSettings::CTA_COLORS
        // and the site's --color-*-500 custom properties in app.css).
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

    <div class="relative flex h-screen w-screen flex-col bg-white">
        {{-- Top bar — branding row + CTA banner, both in normal document
             flow (not absolute). This is the actual layout fix: the bar used
             to float with `absolute top-0` while the wheel/card stage below
             was independently centered in the full viewport, so whenever the
             CTA banner appeared (adding height to the bar) it could overlap
             the stage instead of pushing it down. Flow + flex-1 below means
             the stage always centers in whatever space is actually left. --}}
        <div class="z-30 flex flex-col">
            <div class="flex items-center justify-between px-8 py-6">
                <a href="{{ route('home') }}" class="group flex origin-left items-center gap-2 font-display text-lg font-bold tracking-tight text-slate-900 transition-transform duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] active:translate-x-0.5 active:translate-y-0.5 active:scale-[0.98]">
                    <span class="grid h-9 w-9 place-items-center rounded-lg border-2 border-slate-900 bg-cherry-500 pixel-shadow transition-all duration-150 ease-[cubic-bezier(.34,1.56,.64,1)] group-active:translate-x-0.5 group-active:translate-y-0.5 group-active:scale-95 group-active:shadow-[2px_2px_0_0_#0f172a]"><i data-lucide="ferris-wheel" class="h-5 w-5 text-white transition-transform duration-200 ease-[cubic-bezier(.34,1.56,.64,1)] group-active:rotate-[-20deg] group-active:scale-90"></i></span>
                </a>
                <div class="flex items-center gap-2 font-display text-sm font-bold text-slate-500">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cherry-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-cherry-500"></span>
                    </span>
                    LIVE
                </div>
            </div>

            {{-- Constant CTA banner — one fixed admin-configured message and
                 color (no rotation). Rendered server-side so it's never in a
                 "flash of unstyled content" state; live-view.js only shows
                 or hides it in lockstep with the idle wheel spin, popping it
                 in with a bounce every time it appears. The card body stays
                 white with dark text at all times — only the icon chip and
                 left accent bar carry the admin-chosen color, so a saturated
                 accent can never drop the message below a readable contrast. --}}
            @if (($settings['cta_enabled'] ?? false) && filled($settings['cta_message'] ?? null))
                <div class="flex justify-center px-6 pb-6">
                    <div id="cta-banner" class="hidden max-w-4xl items-stretch overflow-hidden rounded-2xl border-[3px] border-slate-900 bg-white pixel-shadow-lg">
                        <span class="w-3 shrink-0" style="background:{{ $ctaAccentHex }}"></span>
                        <div class="flex items-center gap-4 px-8 py-5">
                            {{--<span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border-2 border-slate-900" style="background:{{ $ctaAccentHex }}">
                                <i data-lucide="megaphone" class="animate-pulse-glow h-7 w-7 text-slate-900"></i>
                               
                            </span>--}}
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl text-5xl leading-none">
                             🎉
                            </div>
                            <span class="min-w-0 font-display text-center text-4xl font-bold uppercase tracking-wide text-slate-900 sm:text-3xl">SPIN & WIN!<br>WIN EXCLUSIVE PRIZES WORTH UP TO RM200!</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Stage — player card + wheel, centered in whatever vertical space
             the top bar leaves behind. --}}
        <div class="relative flex flex-1 flex-col items-center justify-center gap-6 px-6 pb-10">
            {{-- Current player + live prize under the pointer. --}}
            <div class="flex flex-col items-center gap-3 text-center">
                <div id="current-player" class="font-display text-xl font-bold text-brand-600"></div>
                <div id="pointer-prize" class="flex items-center gap-4 rounded-2xl border-[3px] border-slate-900 bg-white px-6 py-4 pixel-shadow-lg">
                    <span id="pointer-prize-chip" class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border-2 border-slate-900" style="background:#0e75bc">
                        <img id="pointer-prize-image" src="" alt="" class="hidden h-full w-full object-cover">
                        <i id="pointer-prize-icon" data-lucide="gift" class="h-8 w-8 text-white"></i>
                    </span>
                    <span id="pointer-prize-name" class="font-display text-2xl font-bold leading-tight text-slate-900">—</span>
                </div>
            </div>

            {{-- Wheel --}}
            <div class="relative aspect-square w-[min(64vh,70vw)]">
                <div id="live-wheel-pointer" class="absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-2 origin-top">
                    <div class="h-0 w-0 border-l-[22px] border-r-[22px] border-t-[38px] border-l-transparent border-r-transparent border-t-cherry-500 drop-shadow-lg"></div>
                </div>
                <div id="wheel-stage" class="h-full w-full"></div>
                <div class="pointer-events-none absolute left-1/2 top-1/2 z-10 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow-lg">
                    <img src="{{ asset('logo-black.png') }}" alt="Logo" class="h-20 w-20 object-contain">
                </div>
            </div>
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

        {{--<div id="enable-sound-overlay" class="absolute inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-6 backdrop-blur-sm">
            <button id="enable-sound-button" type="button" class="btn-primary text-lg">
                <i data-lucide="volume-2" class="h-6 w-6"></i>
                ENABLE SOUND
            </button>
        </div>--}}

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
