<div>
    <x-admin.page-header title="Players" subtitle="Registered participants and their form responses.">
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search email or name…" class="field !py-2 text-sm sm:w-64">
    </x-admin.page-header>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Verified</th>
                        <th class="px-4 py-3">Form</th>
                        <th class="px-4 py-3">Spins</th>
                        <th class="px-4 py-3">Last spin</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($players as $player)
                        <tr class="hover:bg-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $player->email }}</div>
                                @if ($player->display_name)
                                    <div class="text-xs text-slate-500">{{ $player->display_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($player->otp_verified && $player->email_verified_at)
                                    <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Verified</span>
                                @else
                                    <span class="pill bg-slate-100 text-slate-700">Unverified</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($player->form_completed_at)
                                    <i data-lucide="check" class="h-4 w-4 text-emerald-800"></i>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $player->spin_sessions_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $player->last_spin_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($player->isBlocked())
                                    <span class="pill bg-rose-50 text-rose-700 ring-1 ring-rose-300">Blocked</span>
                                @elseif ($player->isOnline())
                                    <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Online
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">{{ $player->last_seen_at?->diffForHumans() ?? 'Offline' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="view({{ $player->id }})" class="text-xs font-semibold text-brand-700 hover:text-brand-600">View</button>
                                    <button wire:click="toggleBlock({{ $player->id }})"
                                            wire:confirm="{{ $player->isBlocked() ? 'Unblock this player?' : 'Block this player from spinning?' }}"
                                            class="text-xs font-semibold {{ $player->isBlocked() ? 'text-emerald-800 hover:text-emerald-900' : 'text-rose-700 hover:text-rose-800' }}">
                                        {{ $player->isBlocked() ? 'Unblock' : 'Block' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No players found.</td></tr>
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
                        <div class="text-slate-900">{{ $viewingPlayer->email }}</div>
                    </div>
                    <div>
                        <div class="label">Display name</div>
                        <div class="text-slate-600">{{ $viewingPlayer->display_name ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Verification</div>
                        @if ($viewingPlayer->otp_verified && $viewingPlayer->email_verified_at)
                            <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Verified</span>
                            <div class="mt-1 text-xs text-slate-500">{{ $viewingPlayer->email_verified_at->format('M j, Y g:i A') }}</div>
                        @else
                            <span class="pill bg-slate-100 text-slate-700">Unverified</span>
                        @endif
                    </div>
                    <div>
                        <div class="label">Form completed</div>
                        <div class="text-slate-600">{{ $viewingPlayer->form_completed_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="label">Total spins</div>
                        <div class="text-slate-600">{{ $viewingPlayer->spin_sessions_count }}</div>
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
                                        <dt class="w-40 shrink-0 text-slate-500">{{ $key }}</dt>
                                        <dd class="text-slate-900">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
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
                                <thead class="border-b border-slate-200 uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">#</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Started</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($viewingPlayer->spinSessions as $session)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-500">{{ $session->id }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ ucfirst($session->status) }}</td>
                                            <td class="px-3 py-2 text-slate-500">{{ $session->started_at?->format('M j, Y g:i A') ?? '—' }}</td>
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
