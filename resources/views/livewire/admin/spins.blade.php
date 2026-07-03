<div>
    <x-admin.page-header title="Spin History" subtitle="Every spin session across your campaigns.">
        <a href="{{ route('admin.spins.export', ['search' => $search, 'campaign_id' => $campaignId, 'status' => $status]) }}" class="btn-primary !py-2 text-sm">Export CSV</a>
    </x-admin.page-header>

    <div class="glass mb-5 rounded-2xl p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="label">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Player email…" class="field">
            </div>
            <div>
                <label class="label">Campaign</label>
                <select wire:model.live="campaignId" class="field">
                    <option value="">All campaigns</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select wire:model.live="status" class="field">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Campaign</th>
                        <th class="px-4 py-3">Player</th>
                        <th class="px-4 py-3">Prize</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Started</th>
                        <th class="px-4 py-3">Completed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($spins as $spin)
                        <tr class="hover:bg-slate-100">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">#{{ $spin->id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $spin->campaign?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $spin->player?->email ?? '—' }}</div>
                                @if ($spin->player?->display_name)
                                    <div class="text-xs text-slate-500">{{ $spin->player->display_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($spin->prize)
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-600">{{ $spin->prize->name }}</span>
                                        <x-rarity-badge :rarity="$spin->prize->rarity" />
                                    </div>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusStyles = [
                                        'completed' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300',
                                        'active' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-300',
                                        'expired' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-300',
                                        'failed' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-300',
                                        'pending' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-300',
                                    ];
                                    $statusCls = $statusStyles[$spin->status] ?? $statusStyles['pending'];
                                @endphp
                                <span class="pill {{ $statusCls }}">{{ ucfirst($spin->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $spin->started_at?->format('M j, Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $spin->completed_at?->format('M j, Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No spins match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $spins->links() }}
    </div>
</div>
