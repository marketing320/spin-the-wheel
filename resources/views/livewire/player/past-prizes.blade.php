<div class="w-full max-w-lg">
    <div class="mb-5 text-center">
        <h1 class="font-display text-2xl font-bold text-slate-900">My Prizes</h1>
        <p class="mt-1 text-sm text-slate-600">Everything you've won, plus any vouchers still waiting to be redeemed.</p>
    </div>

    @if ($results->isEmpty())
        <div class="card text-center">
            <i data-lucide="gift" class="mx-auto h-12 w-12 text-slate-400"></i>
            <p class="mt-3 text-sm text-slate-500">You haven't won anything yet — go give the wheel a spin!</p>
            <a href="{{ route('spin') }}" wire:navigate class="btn-primary mt-5 inline-flex">Spin the wheel</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($results as $result)
                @php $prize = $result->prize; $voucher = $result->voucher; @endphp
                <div class="card">
                    <div class="flex items-center gap-3">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full text-2xl"
                             style="background: {{ $prize?->displayColor() }}22; border: 2px solid {{ $prize?->displayColor() }}88;">
                            @if ($prize?->imageUrl())
                                <img src="{{ $prize->imageUrl() }}" alt="" class="h-11 w-11 rounded-full object-cover">
                            @else
                                <i data-lucide="gift" class="h-6 w-6" style="color: {{ $prize?->displayColor() }}"></i>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-display text-base font-bold text-slate-900">{{ $prize?->name ?? 'Prize' }}</div>
                            <div class="mt-1 flex items-center gap-2">
                                @if ($prize)<x-rarity-badge :rarity="$prize->rarity" />@endif
                                <span class="text-xs text-slate-500">{{ $result->created_at?->format('M j, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($voucher)
                        <div class="mt-4 rounded-xl border-2 border-slate-200 bg-slate-50 p-4">
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
                                    <img src="{{ route('voucher.barcode', $voucher->code) }}" alt="Barcode" class="h-16 flex-1 max-w-[10rem] rounded-lg border-2 border-slate-900 bg-white p-1">
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
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $results->links() }}
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('home') }}" wire:navigate class="btn-ghost">Back to home</a>
    </div>
</div>
