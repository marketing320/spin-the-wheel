<div>
    <x-admin.page-header title="Users" subtitle="Manage back-office accounts for admins and the sales team.">
        <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search name or email…" class="field !py-2 text-sm sm:w-56">
        <button wire:click="create" class="btn-primary !py-2 text-sm">+ New user</button>
    </x-admin.page-header>

    <div class="glass overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Added</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->id === $currentUserId)
                                        <span class="ml-1 text-xs font-medium text-slate-400">(you)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($user->is_admin)
                                        <span class="pill bg-brand-50 text-brand-700 ring-1 ring-brand-200">Admin</span>
                                    @endif
                                    @if ($user->is_staff)
                                        <span class="pill bg-slate-100 text-slate-700">Sales / Staff</span>
                                    @endif
                                    @unless ($user->is_admin || $user->is_staff)
                                        <span class="pill bg-rose-50 text-rose-700 ring-1 ring-rose-300">No access</span>
                                    @endunless
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $user->id }})" class="text-xs font-semibold text-brand-700 hover:text-brand-600">Edit</button>
                                    @if ($user->id !== $currentUserId)
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="Delete {{ $user->email }}? This cannot be undone." class="text-xs font-semibold text-rose-700 hover:text-rose-800">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Danger zone: reset all player + winner data on this instance. --}}
    <div class="mt-8 rounded-2xl border-2 border-rose-300 bg-rose-50 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h3 class="flex items-center gap-2 font-display text-base font-bold text-rose-800">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i> Reset campaign data
                </h3>
                <p class="mt-1 text-sm text-rose-700">
                    Permanently deletes <strong>all players, spins, winners, vouchers, form
                    responses, OTPs</strong> and queue/geofence logs, then restores the active
                    campaign's prize stock. Use this to clear test data before going live.
                    @if ($activeCampaign)
                        Active campaign: <strong>{{ $activeCampaign->name }}</strong>.
                    @else
                        <span class="font-semibold">No active campaign — inventory will not be restored.</span>
                    @endif
                    This cannot be undone.
                </p>
            </div>
            <button wire:click="confirmReset" class="btn-primary !border-rose-700 !bg-rose-600 !py-2 text-sm hover:!bg-rose-700">
                <i data-lucide="trash-2" class="h-4 w-4"></i> Reset data
            </button>
        </div>
    </div>

    {{-- Create / edit user --}}
    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit user' : 'New user'">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model="name" class="field">
                @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" wire:model="email" class="field">
                @error('email') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Password</label>
                    <input type="password" wire:model="password" class="field" autocomplete="new-password">
                    @error('password') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    @if ($editingId)
                        <p class="mt-1 text-xs text-slate-500">Leave blank to keep the current password.</p>
                    @endif
                </div>
                <div>
                    <label class="label">Confirm password</label>
                    <input type="password" wire:model="password_confirmation" class="field" autocomplete="new-password">
                </div>
            </div>
            <div>
                <span class="label">Role</span>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="is_staff" class="h-4 w-4 rounded accent-brand-500">
                        Sales / Staff — dashboard, spin history &amp; voucher redemption
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="is_admin" class="h-4 w-4 rounded accent-brand-500">
                        Admin — full access, including user management
                    </label>
                </div>
                @error('is_staff') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                @error('is_admin') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                <button type="submit" class="btn-primary !py-2 text-sm">Save user</button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Reset confirmation --}}
    <x-admin.modal :show="$showResetModal" :title="'Reset campaign data'">
        <div class="space-y-4">
            <div class="rounded-xl border-2 border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                This deletes every player and all play history across the whole app. There is no undo.
            </div>
            <div>
                <label class="label">Type <span class="font-mono font-bold">RESET</span> to confirm</label>
                <input type="text" wire:model="resetConfirm" class="field" placeholder="RESET" autocomplete="off">
                @error('resetConfirm') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" wire:click="$set('showResetModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                <button type="button" wire:click="resetCampaign" class="btn-primary !border-rose-700 !bg-rose-600 !py-2 text-sm hover:!bg-rose-700">
                    Permanently reset
                </button>
            </div>
        </div>
    </x-admin.modal>
</div>
