<x-layouts.app title="Your Prize">
    <div class="w-full max-w-md" @if ($prize) data-confetti-level="{{ $prize->confetti_level }}" data-rarity="{{ $prize->rarity }}" @endif>
        <div class="card text-center">
            @if ($prize)
                <div class="animate-float-slow mx-auto grid h-24 w-24 place-items-center rounded-full text-5xl"
                     style="background: {{ $prize->displayColor() }}22; border: 2px solid {{ $prize->displayColor() }}88;">
                    @if ($prize->imageUrl())
                        <img src="{{ $prize->imageUrl() }}" alt="" class="h-20 w-20 rounded-full object-cover">
                    @else
                        <i data-lucide="gift" class="h-12 w-12" style="color: {{ $prize->displayColor() }}"></i>
                    @endif
                </div>

                <div class="mt-5 font-display text-sm font-bold uppercase tracking-widest text-brand-600">Congratulations! You won</div>
                <h1 class="mt-1 text-4xl font-bold text-slate-900">{{ $prize->name }}</h1>
                <div class="mt-3 flex justify-center"><x-rarity-badge :rarity="$prize->rarity" /></div>

                @if ($prize->description)
                    <p class="mt-4 text-sm text-slate-600">{{ $prize->description }}</p>
                @endif

                @if ($prize->redemption_message)
                    <div class="mt-6 rounded-xl border-2 border-brand-300 bg-brand-50 p-4 text-left text-sm text-slate-700">
                        <div class="mb-1 font-display font-bold text-brand-700">How to redeem</div>
                        {{ $prize->redemption_message }}
                    </div>
                @endif
            @else
                <i data-lucide="frown" class="mx-auto h-16 w-16 text-slate-400"></i>
                <h1 class="mt-4 text-2xl font-bold text-slate-900">Better luck next time!</h1>
                <p class="mt-3 text-sm text-slate-500">No prize this round.</p>
            @endif

            <div class="mt-8 flex flex-col gap-2">
                <a href="{{ route('home') }}" wire:navigate class="btn-ghost w-full">Back to home</a>
                <a href="{{ route('live-view') }}" target="_blank" class="inline-flex items-center justify-center gap-1 text-xs text-slate-500 hover:text-slate-700">Watch the live screen <i data-lucide="tv" class="h-3.5 w-3.5"></i></a>
            </div>
        </div>
    </div>
</x-layouts.app>
