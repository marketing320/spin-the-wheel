<x-layouts.app :title="$appName">
    <div class="w-full">
        @if ($campaign)
            <div class="mx-auto max-w-3xl text-center">
                <span class="pill bg-brand-500/15 text-brand-300 ring-1 ring-brand-400/30">🎉 Now live</span>
                <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-6xl">
                    {{ $campaign->name }}
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-lg text-slate-300">{{ $tagline }}</p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @if ($player && $player->hasCompletedForm())
                        <a href="{{ route('spin') }}" wire:navigate class="btn-primary animate-pulse-glow text-lg">🎡 Spin the wheel</a>
                    @else
                        <a href="{{ route('player.register') }}" wire:navigate class="btn-primary animate-pulse-glow text-lg">Play now — it's free</a>
                    @endif
                    <a href="{{ route('live-view') }}" target="_blank" class="btn-ghost text-lg">Watch live 📺</a>
                </div>
            </div>

            @if ($prizes->isNotEmpty())
                <div class="mx-auto mt-14 max-w-4xl">
                    <h2 class="mb-5 text-center text-sm font-semibold uppercase tracking-widest text-slate-400">Prizes up for grabs</h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($prizes as $prize)
                            <div class="glass rounded-2xl p-4 text-center transition hover:-translate-y-1">
                                <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full text-2xl"
                                     style="background: {{ $prize->displayColor() }}22; border: 1px solid {{ $prize->displayColor() }}66;">
                                    @if ($prize->imageUrl())
                                        <img src="{{ $prize->imageUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        🎁
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-white">{{ $prize->name }}</div>
                                <x-rarity-badge :rarity="$prize->rarity" class="mt-2" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mx-auto mt-14 grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-4">
                @foreach (['Enter email' => '📧', 'Verify code' => '🔐', 'Fill profile' => '📝', 'Spin & win' => '🏆'] as $step => $icon)
                    <div class="glass rounded-2xl p-4 text-center">
                        <div class="text-2xl">{{ $icon }}</div>
                        <div class="mt-2 text-xs font-medium text-slate-300">{{ $step }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mx-auto max-w-md text-center">
                <div class="text-6xl">🎡</div>
                <h1 class="mt-4 text-3xl font-black text-white">{{ $appName }}</h1>
                <p class="mt-3 text-slate-400">There's no active campaign right now. Please check back soon!</p>
            </div>
        @endif
    </div>
</x-layouts.app>
