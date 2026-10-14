<x-layouts.screen title="Live Winners" js-entry="resources/js/spin/leaderboard.js">
    <main class="live-display relative isolate mx-auto flex h-dvh w-full max-w-[100rem] flex-col overflow-hidden border-x-2 border-slate-900/10 bg-[#faf0e6] text-slate-700">
        <div class="live-display-backdrop" aria-hidden="true">
            <span class="live-orbit live-orbit-one"></span>
            <span class="live-orbit live-orbit-two"></span>
            <span class="live-orbit live-orbit-three"></span>
            <span class="live-sparkle live-sparkle-one">✦</span>
            <span class="live-sparkle live-sparkle-two">✧</span>
            <span class="live-sparkle live-sparkle-three">✦</span>
            <span class="live-sparkle live-sparkle-four">✧</span>
            <span class="live-sparkle live-sparkle-five">✦</span>
        </div>
        <header class="relative z-10 mx-auto flex w-full max-w-[90rem] shrink-0 items-center justify-between border-b-[3px] border-slate-900/10 px-[clamp(0.75rem,4vw,2rem)] py-[clamp(0.5rem,2vh,1rem)]">
            <a href="{{ route('home') }}" class="group flex min-w-0 items-center gap-[clamp(0.4rem,2vw,0.5rem)] font-display text-[clamp(0.55rem,2.6vw,0.65rem)] font-bold tracking-tight text-slate-900">
                <span class="flex h-[clamp(2.25rem,11vw,2.75rem)] items-center rounded-lg border-2 border-slate-900 bg-white px-2.5 pixel-shadow">
                    <img src="{{ asset('logo-black.png') }}" alt="" class="h-[clamp(1.5rem,7vw,1.75rem)] w-auto object-contain">
                </span>
                <span class="truncate">{{ $branding ?: config('app.name') }}</span>
            </a>
            <div class="ml-2 flex shrink-0 items-center gap-2 font-display text-[clamp(0.55rem,2.6vw,0.65rem)] font-bold tracking-[0.15em] text-grass-600">
                <span class="relative flex h-[clamp(0.5rem,2.5vw,0.625rem)] w-[clamp(0.5rem,2.5vw,0.625rem)]">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-grass-500 opacity-75"></span>
                    <span class="relative inline-flex h-full w-full rounded-full bg-grass-500"></span>
                </span>
                LIVE
            </div>
        </header>

        <section class="relative z-10 mx-auto flex w-full max-w-[90rem] min-h-0 flex-1 flex-col px-[clamp(0.75rem,4vw,2rem)] py-[clamp(0.5rem,2.5vh,1.25rem)]">
            <div class="mb-[clamp(0.5rem,2.5vh,1.25rem)] shrink-0 text-center">
                <p class="font-display text-[clamp(0.55rem,2.6vw,0.65rem)] font-bold uppercase tracking-[0.15em] text-brand-600">
                    {{ $campaign?->name ?? 'Prize winners' }}
                </p>
                <h1 class="mt-[clamp(0.5rem,2.5vh,1rem)] font-display text-[clamp(1rem,5vw,1.25rem)] font-bold leading-tight text-slate-900">
                    Latest winners
                </h1>
                <p class="mt-[clamp(0.375rem,1.8vh,0.75rem)] text-[clamp(0.75rem,3.5vw,0.875rem)] font-semibold text-slate-600">Every prize, newest win first</p>
            </div>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border-[3px] border-slate-900 bg-white pixel-shadow-lg">
                <div class="grid grid-cols-[clamp(3.75rem,21vw,4.25rem)_minmax(0,1fr)] items-center gap-[clamp(0.375rem,2vw,0.5rem)] border-b-2 border-slate-200 bg-slate-50 px-[clamp(0.5rem,3vw,0.75rem)] py-[clamp(0.5rem,1.8vh,0.75rem)] text-[clamp(0.5rem,2.4vw,0.6rem)] font-bold uppercase tracking-[0.1em] text-slate-500">
                    <span>Won at</span>
                    <span>Winner</span>
                </div>
                <ol id="leaderboard-list" data-leaderboard-url="{{ $leaderboardRoute }}" class="live-winners-grid min-h-0 flex-1 overflow-y-auto">
                    @forelse ($winners as $winner)
                        <li class="live-winner-card grid grid-cols-[clamp(3.75rem,21vw,4.25rem)_minmax(0,1fr)] items-center gap-[clamp(0.375rem,2vw,0.5rem)] rounded-xl border-2 border-slate-100 px-[clamp(0.75rem,2vw,1rem)] py-[clamp(0.75rem,1.8vh,1rem)]">
                            <time datetime="{{ $winner['won_at'] }}" class="font-display text-[clamp(0.48rem,2.2vw,0.55rem)] leading-relaxed text-brand-600">{{ \Illuminate\Support\Carbon::parse($winner['won_at'])->format('M j, g:i A') }}</time>
                            <span class="min-w-0 truncate font-display text-[clamp(0.62rem,2.7vw,0.75rem)] font-bold text-slate-900">{{ $winner['name'] }}</span>
                            <span class="col-start-2 row-start-2 flex min-w-0 items-center gap-[clamp(0.25rem,1.5vw,0.5rem)]">
                                <span class="truncate text-[clamp(0.62rem,2.7vw,0.75rem)] font-semibold text-slate-600">{{ $winner['prize'] }}</span>
                                <x-rarity-badge :rarity="$winner['rarity']" class="!shrink-0 !px-[clamp(0.375rem,2vw,0.5rem)] !py-0.5 !text-[clamp(0.45rem,2vw,0.55rem)]" />
                            </span>
                        </li>
                    @empty
                        <li id="leaderboard-empty" class="px-4 py-[clamp(1.5rem,8vh,3rem)] text-center font-display text-[clamp(0.62rem,2.7vw,0.75rem)] leading-relaxed text-slate-500">No prizes have been won yet. Be the first!</li>
                    @endforelse
                </ol>
            </div>
            <p id="leaderboard-status" role="status" aria-live="polite" class="mt-[clamp(0.375rem,1.8vh,0.75rem)] min-h-5 shrink-0 text-center text-[clamp(0.55rem,2.4vw,0.65rem)] font-semibold text-slate-500">Live winners · updates automatically</p>
        </section>
    </main>
</x-layouts.screen>
