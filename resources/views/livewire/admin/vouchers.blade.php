<div>
    <x-admin.page-header title="Vouchers" subtitle="Issued voucher codes, redemption history, and expired-stock rotation.">
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search code, prize or customer…" class="field !py-2 text-sm sm:w-64">
        @if ($isAdmin)
            @if ($counts['expired'] > 0)
                <button wire:click="rotateAllExpired"
                        data-swal-confirm-title="Rotate expired vouchers?"
                        data-swal-confirm="Rotate all {{ $counts['expired'] }} expired voucher(s) back onto the wheel? Their prize stock and odds will be restored."
                        data-swal-confirm-button="Rotate all"
                        data-swal-confirm-tone="primary"
                        class="btn-ghost !py-2 text-sm">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i> Rotate expired ({{ $counts['expired'] }})
                </button>
            @endif
            <a href="{{ route('admin.vouchers.export', $exportParams) }}" class="btn-primary !py-2 text-sm">
                <i data-lucide="download" class="h-4 w-4"></i> Export CSV
            </a>
        @endif
    </x-admin.page-header>

    {{-- Status tabs --}}
    @php
        $tabs = [
            'all' => 'All',
            'pending' => 'Active',
            'redeemed' => 'Redeemed',
            'expired' => 'Expired',
            'rotated' => 'Rotated',
        ];
    @endphp
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" @class([
                'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition',
                'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $filter === $key,
                'text-slate-600 hover:bg-slate-100' => $filter !== $key,
            ])>
                {{ $label }}
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $counts[$key] }}</span>
            </button>
        @endforeach
    </div>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Prize</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3">Redeemed</th>
                        @if ($isAdmin)<th class="px-4 py-3 text-right">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($vouchers as $v)
                        @php
                            $status = $v->displayStatus();
                            $email = $isAdmin ? $v->player?->email : \App\Support\PrivacyMask::reveal3($v->player?->email);
                            $name = $isAdmin ? $v->player?->display_name : \App\Support\PrivacyMask::reveal3($v->player?->display_name);
                        @endphp
                        <tr class="hover:bg-slate-100">
                            <td class="px-4 py-3 font-display tracking-widest text-slate-900">{{ $v->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $v->prize?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ ucfirst($v->prize?->rarity ?? '') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-slate-800">{{ $email ?: '—' }}</div>
                                @if ($name)<div class="text-xs text-slate-500">{{ $name }}</div>@endif
                            </td>
                            <td class="px-4 py-3">
                                @switch($status)
                                    @case('redeemed')
                                        <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Redeemed</span>
                                        @break
                                    @case('rotated')
                                        <span class="pill bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300">Rotated</span>
                                        @break
                                    @case('expired')
                                        <span class="pill bg-rose-50 text-rose-700 ring-1 ring-rose-300">Expired</span>
                                        @break
                                    @default
                                        <span class="pill bg-amber-50 text-amber-800 ring-1 ring-amber-300">Active</span>
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $v->expires_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                @if ($v->redeemed_at)
                                    <div class="text-slate-700">{{ $v->redeemed_at->format('M j, Y g:i A') }}</div>
                                    <div>by {{ $v->redeemedByUser?->name ?? 'Unknown' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            @if ($isAdmin)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($status === 'expired')
                                            <button wire:click="rotate({{ $v->id }})"
                                                    data-swal-confirm-title="Rotate voucher?"
                                                    data-swal-confirm="Rotate voucher {{ $v->code }} back onto the wheel? Its prize stock and odds will be restored."
                                                    data-swal-confirm-button="Rotate"
                                                    data-swal-confirm-tone="primary"
                                                    class="text-xs font-semibold text-indigo-700 hover:text-indigo-800">
                                                Rotate to wheel
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" class="px-4 py-10 text-center text-slate-500">No vouchers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $vouchers->links() }}
    </div>
</div>
