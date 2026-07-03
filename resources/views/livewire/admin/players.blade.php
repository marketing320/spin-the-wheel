<div>
    <x-admin.page-header title="Players" subtitle="Registered participants and their form responses.">
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search email or name…" class="field !py-2 text-sm sm:w-64">
    </x-admin.page-header>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Verified</th>
                        <th class="px-4 py-3">Form</th>
                        <th class="px-4 py-3">Spins</th>
                        <th class="px-4 py-3">Last spin</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($players as $player)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-white">{{ $player->email }}</div>
                                @if ($player->display_name)
                                    <div class="text-xs text-slate-500">{{ $player->display_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($player->otp_verified && $player->email_verified_at)
                                    <span class="pill bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">Verified</span>
                                @else
                                    <span class="pill bg-slate-500/20 text-slate-300">Unverified</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                @if ($player->form_completed_at)
                                    <span class="text-emerald-300">✓</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $player->spin_sessions_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-400">
                                {{ $player->last_spin_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="view({{ $player->id }})" class="text-xs font-semibold text-brand-300 hover:text-brand-200">View</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No players found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $players->links() }}
    </div>

    <x-admin.modal :show="$showModal" :title="'Player details'">
        @if ($viewingPlayer)
            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="label">Email</div>
                        <div class="text-white">{{ $viewingPlayer->email }}</div>
                    </div>
                    <div>
                        <div class="label">Display name</div>
                        <div class="text-slate-300">{{ $viewingPlayer->display_name ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Verification</div>
                        @if ($viewingPlayer->otp_verified && $viewingPlayer->email_verified_at)
                            <span class="pill bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">Verified</span>
                            <div class="mt-1 text-xs text-slate-500">{{ $viewingPlayer->email_verified_at->format('M j, Y g:i A') }}</div>
                        @else
                            <span class="pill bg-slate-500/20 text-slate-300">Unverified</span>
                        @endif
                    </div>
                    <div>
                        <div class="label">Form completed</div>
                        <div class="text-slate-300">{{ $viewingPlayer->form_completed_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Total spins</div>
                        <div class="text-slate-300">{{ $viewingPlayer->spin_sessions_count }}</div>
                    </div>
                </div>

                @php($response = $viewingPlayer->formResponses->first())
                <div>
                    <div class="label mb-2">Form response</div>
                    @if ($response && ! empty($response->responses))
                        <div class="glass rounded-xl p-3 text-sm">
                            @if ($response->campaign)
                                <div class="mb-2 text-xs text-slate-500">{{ $response->campaign->name }}</div>
                            @endif
                            <dl class="space-y-1.5">
                                @foreach ($response->responses as $key => $value)
                                    <div class="flex gap-3">
                                        <dt class="w-40 shrink-0 text-slate-400">{{ $key }}</dt>
                                        <dd class="text-white">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No form response submitted.</p>
                    @endif
                </div>

                <div>
                    <div class="label mb-2">Recent spins</div>
                    @if ($viewingPlayer->spinSessions->isNotEmpty())
                        <div class="glass rounded-xl">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-white/10 uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2">#</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Started</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($viewingPlayer->spinSessions as $session)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-400">{{ $session->id }}</td>
                                            <td class="px-3 py-2 text-slate-300">{{ ucfirst($session->status) }}</td>
                                            <td class="px-3 py-2 text-slate-400">{{ $session->started_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No spins yet.</p>
                    @endif
                </div>

                <div class="flex justify-end pt-1">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Close</button>
                </div>
            </div>
        @endif
    </x-admin.modal>
</div>
