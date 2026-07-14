<x-layouts.app :title="$appName" :jsEntry="['resources/js/app.js', 'resources/js/landing.js']">
    @push('head')
        <link rel="icon" href="{{ asset('favicon.ico') }}">

        <meta name="description" content="{{ $tagline }}">
        <link rel="canonical" href="{{ route('home') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:title" content="{{ $appName }}">
        <meta property="og:description" content="{{ $tagline }}">
        <meta property="og:url" content="{{ route('home') }}">
        <meta property="og:image" content="{{ asset('favicon.ico') }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $appName }}">
        <meta name="twitter:description" content="{{ $tagline }}">
        <meta name="twitter:image" content="{{ asset('favicon.ico') }}">
    @endpush

    <div class="w-full">
        @if ($campaign)
            {{-- Hero --}}
            <div class="mx-auto max-w-3xl text-center">
                <span class="pill border-2 border-slate-900 bg-grass-500 font-display uppercase text-white pixel-shadow">
                    <span class="inline-block h-2 w-2 rounded-full bg-white"></span> Now live
                </span>

                <h1 class="mt-5 font-display text-4xl font-bold leading-tight text-slate-900 sm:text-6xl">
                    {{ $campaign->name }}
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-lg font-semibold text-slate-600">{{ $tagline }}</p>

                {{-- Decorative pixel color bar --}}
                <div class="mx-auto mt-6 flex w-max gap-1.5">
                    @foreach (['bg-cherry-500','bg-brand-500','bg-sun-500','bg-grass-500','bg-grape-500','bg-tangerine-500'] as $c)
                        <span class="h-3 w-3 {{ $c }} border-2 border-slate-900"></span>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @if ($player && $player->hasCompletedForm())
                        <a href="{{ route('spin') }}" class="pressable-control btn-primary animate-pulse-glow text-lg"><i data-lucide="ferris-wheel" class="h-5 w-5"></i> Spin the wheel</a>
                    @else
                        <a href="{{ route('player.register') }}" wire:navigate class="pressable-control btn-primary animate-pulse-glow text-lg"><i data-lucide="play" class="h-5 w-5"></i> Play now</a>
                    @endif
                    <a href="{{ route('live-view') }}" target="_blank" class="btn-ghost text-lg"><i data-lucide="tv" class="h-5 w-5"></i> Watch live</a>
                </div>
            </div>

            {{-- Prizes --}}
            @if ($prizes->isNotEmpty())
                <div class="mx-auto mt-16 w-full max-w-5xl">
                    <h2 class="mb-6 text-center font-display text-sm font-bold uppercase tracking-widest text-brand-600">Prizes up for grabs</h2>

                    <div class="prize-slider">
                        <div class="relative">
                            <div class="prize-swiper swiper !py-8">
                                <div class="swiper-wrapper !items-stretch">
                                    @foreach ($prizes as $prize)
                                        <div class="swiper-slide !h-auto !w-60 sm:!w-72">
                                            <div class="flex h-full flex-col rounded-2xl border-[3px] border-slate-900 bg-white p-5 text-center pixel-shadow">
                                                <div class="mx-auto mb-4 grid h-24 w-24 place-items-center rounded-xl border-2 border-slate-900"
                                                     style="background: {{ $prize->displayColor() }}">
                                                    @if ($prize->imageUrl())
                                                        <img src="{{ $prize->imageUrl() }}" alt="" class="h-20 w-20 rounded-lg object-cover">
                                                    @else
                                                        <i data-lucide="gift" class="h-10 w-10 text-white"></i>
                                                    @endif
                                                </div>
                                                <div class="font-display text-sm font-bold text-slate-900 [word-break:break-word]">{{ $prize->name }}</div>
                                                <div class="mt-2 flex justify-center">
                                                    <x-rarity-badge :rarity="$prize->rarity" />
                                                </div>
                                                @if ($prize->description)
                                                    <p class="mt-3 line-clamp-3 text-xs font-semibold leading-relaxed text-slate-500">{{ $prize->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($prizes->count() > 1)
                                <button type="button" class="prize-swiper-prev pressable-control absolute left-1 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow transition hover:bg-slate-100 sm:-left-3" aria-label="Previous prize">
                                    <i data-lucide="chevron-left" class="h-5 w-5 text-slate-900"></i>
                                </button>
                                <button type="button" class="prize-swiper-next pressable-control absolute right-1 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border-[3px] border-slate-900 bg-white pixel-shadow transition hover:bg-slate-100 sm:-right-3" aria-label="Next prize">
                                    <i data-lucide="chevron-right" class="h-5 w-5 text-slate-900"></i>
                                </button>
                            @endif
                        </div>

                        @if ($prizes->count() > 1)
                            <div class="prize-swiper-pagination mt-5 flex items-center justify-center gap-2"></div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- How it works 
            <div class="mx-auto mt-16 max-w-3xl">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([
                        ['Enter email', 'mail', 'bg-cherry-500'],
                        ['Verify code', 'shield-check', 'bg-brand-500'],
                        ['Fill profile', 'clipboard-list', 'bg-grape-500'],
                        ['Spin & win', 'trophy', 'bg-sun-500'],
                    ] as [$step, $icon, $bg])
                        <div class="rounded-2xl border-[3px] border-slate-900 bg-white p-4 text-center pixel-shadow">
                            <div class="mx-auto grid h-11 w-11 place-items-center rounded-lg border-2 border-slate-900 {{ $bg }}">
                                <i data-lucide="{{ $icon }}" class="h-5 w-5 text-white"></i>
                            </div>
                            <div class="mt-3 font-display text-xs font-bold text-slate-900">{{ $step }}</div>
                        </div>
                    @endforeach
                </div>
            </div>--}}
        @else
            <div class="mx-auto max-w-md text-center">
                <div class="mx-auto grid h-20 w-20 place-items-center rounded-2xl border-[3px] border-slate-900 bg-brand-500 pixel-shadow">
                    <i data-lucide="ferris-wheel" class="h-10 w-10 text-white"></i>
                </div>
                <h1 class="mt-6 font-display text-3xl font-bold text-slate-900">{{ $appName }}</h1>
                <p class="mt-3 font-semibold text-slate-600">There's no active campaign right now. Please check back soon!</p>
            </div>
        @endif
    </div>
</x-layouts.app>
