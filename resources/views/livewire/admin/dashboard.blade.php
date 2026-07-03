<div class="space-y-6">
    @if (! $campaign)
        <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            No active campaign. <a href="{{ route('admin.campaigns') }}" class="font-semibold underline">Create or activate one →</a>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Registered players', $totalPlayers, '👥', 'text-sky-300'],
            ['Verified players', $verifiedPlayers, '✅', 'text-emerald-300'],
            ['Total spins', $totalSpins, '🎡', 'text-brand-300'],
            ['Prizes won', $totalWins, '🏆', 'text-amber-300'],
        ] as [$label, $value, $icon, $color])
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">{{ $label }}</span>
                    <span class="text-xl">{{ $icon }}</span>
                </div>
                <div class="mt-2 text-3xl font-black {{ $color }}">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Active spin --}}
        <div class="glass rounded-2xl p-5">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-slate-400">Live status</h2>
            @if ($activeSpin)
                <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-4">
                    <div class="flex items-center gap-2 text-emerald-300">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <span class="font-semibold">Spin in progress</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-300">Player #{{ $activeSpin->player_id }} · started {{ $activeSpin->started_at?->diffForHumans() }}</p>
                </div>
            @else
                <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-400">
                    ⚪ Idle — no active spin right now.
                </div>
            @endif
        </div>

        {{-- Wins by prize --}}
        <div class="glass rounded-2xl p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-slate-400">Wins by prize</h2>
            @if ($winsByPrize->isEmpty())
                <p class="text-sm text-slate-500">No prizes have been won yet.</p>
            @else
                <div class="space-y-2">
                    @php $max = $winsByPrize->max('total') ?: 1; @endphp
                    @foreach ($winsByPrize as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="text-slate-200">{{ $row->prize?->name ?? 'Unknown' }}</span>
                                <span class="font-semibold text-white">{{ $row->total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-pink-500" style="width: {{ round($row->total / $max * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
