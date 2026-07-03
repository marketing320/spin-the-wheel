<x-layouts.app title="Your Prize">
    <div class="w-full max-w-md" @if ($prize) data-confetti-level="{{ $prize->confetti_level }}" data-rarity="{{ $prize->rarity }}" @endif>
        <div class="card text-center">
            @if ($prize)
                <div class="animate-float-slow mx-auto grid h-24 w-24 place-items-center rounded-full text-5xl"
                     style="background: {{ $prize->displayColor() }}22; border: 2px solid {{ $prize->displayColor() }}88;">
                    @if ($prize->imageUrl())
                        <img src="{{ $prize->imageUrl() }}" alt="" class="h-20 w-20 rounded-full object-cover">
                    @else
                        🎁
                    @endif
                </div>

                <div class="mt-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Congratulations! You won</div>
                <h1 class="mt-1 text-4xl font-black text-white">{{ $prize->name }}</h1>
                <div class="mt-3 flex justify-center"><x-rarity-badge :rarity="$prize->rarity" /></div>

                @if ($prize->description)
                    <p class="mt-4 text-sm text-slate-300">{{ $prize->description }}</p>
                @endif

                @if ($prize->redemption_message)
                    <div class="mt-6 rounded-xl border border-brand-400/30 bg-brand-500/10 p-4 text-left text-sm text-brand-100">
                        <div class="mb-1 font-semibold text-brand-200">How to redeem</div>
                        {{ $prize->redemption_message }}
                    </div>
                @endif
            @else
                <div class="text-6xl">🙈</div>
                <h1 class="mt-4 text-2xl font-black text-white">Better luck next time!</h1>
                <p class="mt-3 text-sm text-slate-400">No prize this round.</p>
            @endif

            <div class="mt-8 flex flex-col gap-2">
                <a href="{{ route('home') }}" wire:navigate class="btn-ghost w-full">Back to home</a>
                <a href="{{ route('live-view') }}" target="_blank" class="text-xs text-slate-500 hover:text-slate-300">Watch the live screen 📺</a>
            </div>
        </div>
    </div>
</x-layouts.app>
