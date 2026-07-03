<div>
    <x-admin.page-header title="Campaigns" subtitle="Create and control your prize campaigns.">
        <button wire:click="create" class="btn-primary !py-2 text-sm">+ New campaign</button>
    </x-admin.page-header>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Prizes</th>
                        <th class="px-4 py-3">Spins</th>
                        <th class="px-4 py-3">Window</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse ($campaigns as $c)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-white">{{ $c->name }}</div>
                                <div class="text-xs text-slate-500">{{ $c->slug }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($c->active)
                                    <span class="pill bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">● Active</span>
                                @else
                                    <span class="pill bg-slate-500/20 text-slate-300">{{ ucfirst($c->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ ucfirst($c->prize_mode) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $c->prizes_count }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $c->spin_sessions_count }}</td>
                            <td class="px-4 py-3 text-xs text-slate-400">
                                {{ $c->starts_at?->format('M j, Y') ?? '—' }} → {{ $c->ends_at?->format('M j, Y') ?? '∞' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @unless ($c->active)
                                        <button wire:click="activate({{ $c->id }})" class="text-xs font-semibold text-emerald-300 hover:text-emerald-200">Activate</button>
                                    @endunless
                                    <button wire:click="edit({{ $c->id }})" class="text-xs font-semibold text-brand-300 hover:text-brand-200">Edit</button>
                                    <button wire:click="delete({{ $c->id }})" wire:confirm="Delete this campaign and all its data?" class="text-xs font-semibold text-rose-400 hover:text-rose-300">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No campaigns yet. Create your first one!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit campaign' : 'New campaign'">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model.live.debounce.400ms="name" class="field">
                @error('name') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Slug</label>
                <input type="text" wire:model="slug" class="field">
                @error('slug') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Status</label>
                    <select wire:model="status" class="field">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>
                <div>
                    <label class="label">Prize mode</label>
                    <select wire:model="prize_mode" class="field">
                        <option value="weighted">Weighted</option>
                        <option value="strict">Strict %</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Starts at</label>
                    <input type="datetime-local" wire:model="starts_at" class="field">
                </div>
                <div>
                    <label class="label">Ends at</label>
                    <input type="datetime-local" wire:model="ends_at" class="field">
                    @error('ends_at') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" wire:model="active" class="h-4 w-4 rounded accent-brand-500">
                Make this the active campaign
            </label>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                <button type="submit" class="btn-primary !py-2 text-sm">Save campaign</button>
            </div>
        </form>
    </x-admin.modal>
</div>
