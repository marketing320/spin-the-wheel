<div>
    <x-admin.page-header title="Change password" subtitle="Update the password for your own account." />

    <div class="glass max-w-lg rounded-2xl p-6">
        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="label">Current password</label>
                <input type="password" wire:model="current_password" class="field" autocomplete="current-password">
                @error('current_password') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">New password</label>
                <input type="password" wire:model="password" class="field" autocomplete="new-password">
                @error('password') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Confirm new password</label>
                <input type="password" wire:model="password_confirmation" class="field" autocomplete="new-password">
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary !py-2 text-sm">Update password</button>
            </div>
        </form>
    </div>
</div>
