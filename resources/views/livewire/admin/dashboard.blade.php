<div class="space-y-6">
    @if (! $campaign)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No active campaign. <a href="{{ route('admin.campaigns') }}" class="font-semibold underline">Create or activate one <i data-lucide="arrow-right" class="inline h-4 w-4"></i></a>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Registered players', $totalPlayers, 'users', 'text-sky-700'],
            ['Verified players', $verifiedPlayers, 'badge-check', 'text-emerald-800'],
            ['Total spins', $totalSpins, 'ferris-wheel', 'text-brand-700'],
            ['Prizes won', $totalWins, 'trophy', 'text-amber-800'],
        ] as [$label, $value, $icon, $color])
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ $label }}</span>
                    <i data-lucide="{{ $icon }}" class="h-6 w-6 {{ $color }}"></i>
                </div>
                <div class="mt-2 text-3xl font-black {{ $color }}">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Active spin --}}
        <div class="glass rounded-2xl p-5">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-slate-500 font-display">Live status</h2>
            @if ($activeSpin)
                <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4">
                    <div class="flex items-center gap-2 text-emerald-800">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <span class="font-semibold">Spin in progress</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Player #{{ $activeSpin->player_id }} · started {{ $activeSpin->started_at?->diffForHumans() }}</p>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-100 p-4 text-sm text-slate-500">
                    <span class="inline-block h-2 w-2 rounded-full bg-slate-500"></span> Idle — no active spin right now.
                </div>
            @endif
        </div>

        {{-- Wins by prize --}}
        <div class="glass rounded-2xl p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-slate-500 font-display">Wins by prize</h2>
            @if ($winsByPrize->isEmpty())
                <p class="text-sm text-slate-500">No prizes have been won yet.</p>
            @else
                <div class="space-y-2">
                    @php $max = $winsByPrize->max('total') ?: 1; @endphp
                    @foreach ($winsByPrize as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="text-slate-800">{{ $row->prize?->name ?? 'Unknown' }}</span>
                                <span class="font-semibold text-slate-900">{{ $row->total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-cherry-500" style="width: {{ round($row->total / $max * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
