<div>
    <x-admin.page-header title="Settings"
        subtitle="Global configuration for verification, spins, and branding." />

    <div class="glass card max-w-2xl rounded-2xl p-6">
        <form wire:submit="save" class="space-y-8">
            {{-- OTP & verification --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-300">OTP &amp; verification</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Controls how email one-time passwords expire, how often they can be resent, and how many attempts are allowed.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">OTP expiry (minutes)</label>
                        <input type="number" wire:model="otp_expiry_minutes" min="1" max="120" class="field">
                        @error('otp_expiry_minutes') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Resend cooldown (seconds)</label>
                        <input type="number" wire:model="otp_resend_cooldown_seconds" min="10" max="3600" class="field">
                        @error('otp_resend_cooldown_seconds') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Max attempts</label>
                        <input type="number" wire:model="otp_max_attempts" min="1" max="20" class="field">
                        @error('otp_max_attempts') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Spin --}}
            <div class="border-t border-white/10 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Spin</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Failsafe lock timeout and the default wheel animation duration used when a spin starts.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Lock timeout (seconds)</label>
                        <input type="number" wire:model="spin_lock_timeout_seconds" min="10" max="600" class="field">
                        <p class="mt-1 text-xs text-slate-500">Stuck spin locks are released after this many seconds.</p>
                        @error('spin_lock_timeout_seconds') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Default animation duration (ms)</label>
                        <input type="number" wire:model="spin_default_duration_ms" min="2000" max="20000" class="field">
                        <p class="mt-1 text-xs text-slate-500">Total wheel spin time in milliseconds (2000–20000).</p>
                        @error('spin_default_duration_ms') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Branding --}}
            <div class="border-t border-white/10 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Branding</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Public-facing app name, tagline, and terms &amp; conditions text.
                    </p>
                </div>

                <div>
                    <label class="label">App name</label>
                    <input type="text" wire:model="app_name" class="field">
                    @error('app_name') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Tagline</label>
                    <input type="text" wire:model="tagline" class="field" placeholder="Spin to win amazing prizes!">
                    @error('tagline') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Terms &amp; conditions</label>
                    <textarea wire:model="terms" rows="5" class="field"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Shown to players where terms are referenced. Leave blank to hide.</p>
                    @error('terms') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary !py-2 text-sm">Save settings</button>
            </div>
        </form>
    </div>
</div>
