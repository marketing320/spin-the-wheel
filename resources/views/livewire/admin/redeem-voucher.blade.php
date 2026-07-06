<div x-data x-on:voucher-scanned.window="$wire.set('codeInput', $event.detail.code); $wire.call('lookup')">
    <x-admin.page-header title="Redeem Voucher" subtitle="Scan or enter a customer's voucher code to review and redeem it." />

    @if ($error)
        <div class="mb-4 rounded-xl border-2 border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ $error }}
        </div>
    @endif

    @if (! $pending)
        <div class="glass rounded-2xl p-5">
            <form wire:submit="lookup" class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    wire:model="codeInput"
                    autofocus
                    autocomplete="off"
                    placeholder="Scan or type the voucher code…"
                    class="field flex-1 font-display uppercase tracking-widest"
                >
                <button type="submit" class="btn-primary whitespace-nowrap">
                    <i data-lucide="search" class="h-4 w-4"></i> Look up
                </button>
            </form>

            {{-- Hardware barcode scanners act as a keyboard: focusing the field
                 above and scanning "types" the code + Enter, submitting the
                 form automatically. The camera scanner below is an alternative
                 for staff without a dedicated scanner. --}}
            <div class="mt-4 border-t-2 border-slate-100 pt-4" wire:ignore>
                <button type="button" id="redeem-scanner-toggle" class="btn-ghost text-sm">
                    <i data-lucide="camera" class="h-4 w-4"></i> Scan with camera
                </button>
                <div id="redeem-scanner-region" class="hidden mt-3 max-w-sm overflow-hidden rounded-xl border-2 border-slate-900"></div>
            </div>
        </div>
    @else
        <div class="card" wire:key="pending-{{ $pending['voucher_id'] }}"
             x-data="{
                expiresAt: new Date(@js($pending['expires_at'])).getTime(),
                remaining: '',
                tick() {
                    if (!document.body.contains(this.$el)) { clearInterval(this.intervalId); return; }
                    const diff = this.expiresAt - Date.now();
                    if (diff <= 0) { this.remaining = 'Expired'; clearInterval(this.intervalId); return; }
                    const h = Math.floor(diff / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);
                    this.remaining = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },
                intervalId: null,
             }"
             x-init="tick(); intervalId = setInterval(() => tick(), 1000)">
            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border-2 border-slate-900 bg-slate-50">
                    @if ($pending['prize_image'])
                        <img src="{{ $pending['prize_image'] }}" alt="" class="h-full w-full object-cover">
                    @else
                        <i data-lucide="gift" class="h-8 w-8 text-slate-400"></i>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-display text-lg font-bold text-slate-900">{{ $pending['prize_name'] ?? 'Prize' }}</div>
                    <div class="font-display text-sm tracking-widest text-brand-600">{{ $pending['code'] }}</div>
                </div>
                @if ($pending['status'] === 'redeemed')
                    <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Already redeemed</span>
                @elseif ($pending['status'] === 'expired')
                    <span class="pill bg-rose-50 text-rose-700 ring-1 ring-rose-300">Expired</span>
                @else
                    <span class="pill bg-brand-50 text-brand-700 ring-1 ring-brand-300" x-text="remaining"></span>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 rounded-xl border-2 border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                <div>
                    <div class="label">Email</div>
                    <div class="text-sm font-semibold text-slate-800">{{ $pending['masked_email'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="label">Full name</div>
                    <div class="text-sm font-semibold text-slate-800">{{ $pending['masked_full_name'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="label">Phone</div>
                    <div class="text-sm font-semibold text-slate-800">{{ $pending['masked_phone'] ?? '—' }}</div>
                </div>
            </div>

            @unless ($pending['redeemable'])
                <p class="mt-3 text-sm text-slate-500">This voucher can no longer be redeemed.</p>
            @endunless

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="btn-ghost">Cancel</button>
                @if ($pending['redeemable'])
                    <button type="button" wire:click="confirmRedeem" wire:confirm="Redeem this voucher now? This cannot be undone." class="btn-primary">
                        <i data-lucide="check" class="h-4 w-4"></i> Confirm Redeem
                    </button>
                @endif
            </div>
        </div>
    @endif

    @vite(['resources/js/admin/redeem-scanner.js'])
</div>
