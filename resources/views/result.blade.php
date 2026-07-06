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

                @if ($voucher)
                    <div class="mt-4 rounded-xl border-2 border-slate-200 bg-slate-50 p-4 text-left">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-display text-sm font-bold tracking-widest text-slate-900">{{ $voucher->code }}</span>
                            @if ($voucher->isRedeemed())
                                <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Redeemed</span>
                            @elseif ($voucher->isExpired())
                                <span class="pill bg-rose-50 text-rose-700 ring-1 ring-rose-300">Expired</span>
                            @else
                                <span class="pill bg-brand-50 text-brand-700 ring-1 ring-brand-300">Valid until {{ $voucher->expires_at->format('M j, g:i A') }}</span>
                            @endif
                        </div>

                        @if ($voucher->isRedeemable())
                            <div class="mt-3 flex items-center justify-center gap-4">
                                <img src="{{ route('voucher.qr', $voucher->code) }}" alt="QR code" class="h-24 w-24 rounded-lg border-2 border-slate-900 bg-white p-1">
                                <img src="{{ route('voucher.barcode', $voucher->code) }}" alt="Barcode" class="h-16 max-w-[10rem] flex-1 rounded-lg border-2 border-slate-900 bg-white p-1">
                            </div>
                            <p class="mt-2 text-center text-xs text-slate-500">Show this to staff to redeem.</p>

                            {{-- Live countdown to expiry: "Xd Yh Zm" while more than a day
                                 remains, switching to a ticking HH:MM:SS once under 24h. --}}
                            <div class="mt-3 text-center"
                                 x-data="{
                                    expiresAt: new Date(@js($voucher->expires_at->toIso8601String())).getTime(),
                                    remaining: '',
                                    expired: false,
                                    tick() {
                                        if (!document.body.contains(this.$el)) { clearInterval(this.intervalId); return; }
                                        const diff = this.expiresAt - Date.now();
                                        if (diff <= 0) { this.expired = true; clearInterval(this.intervalId); return; }
                                        const totalSeconds = Math.floor(diff / 1000);
                                        const days = Math.floor(totalSeconds / 86400);
                                        const hours = Math.floor((totalSeconds % 86400) / 3600);
                                        const minutes = Math.floor((totalSeconds % 3600) / 60);
                                        const seconds = totalSeconds % 60;
                                        if (days >= 1) {
                                            this.remaining = `${days}d ${hours}h ${minutes}m`;
                                        } else {
                                            const pad = (n) => String(n).padStart(2, '0');
                                            this.remaining = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                                        }
                                    },
                                    intervalId: null,
                                 }"
                                 x-init="tick(); intervalId = setInterval(() => tick(), 1000)">
                                <template x-if="!expired">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Time remaining</div>
                                        <div class="font-display text-lg font-bold text-cherry-600" x-text="remaining"></div>
                                    </div>
                                </template>
                                <template x-if="expired">
                                    <div class="font-display text-sm font-bold text-rose-600">Expired</div>
                                </template>
                            </div>
                        @endif
                    </div>
                @endif

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
