<div>
    <x-admin.page-header title="Settings"
        subtitle="Global configuration for verification, spins, and branding." />

    <div class="glass card max-w-2xl rounded-2xl p-6">
        <form wire:submit="save" class="space-y-8">
            {{-- OTP & verification --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">OTP &amp; verification</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Controls how email one-time passwords expire, how often they can be resent, and how many attempts are allowed.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">OTP expiry (minutes)</label>
                        <input type="number" wire:model="otp_expiry_minutes" min="1" max="120" class="field">
                        @error('otp_expiry_minutes') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Resend cooldown (seconds)</label>
                        <input type="number" wire:model="otp_resend_cooldown_seconds" min="10" max="3600" class="field">
                        @error('otp_resend_cooldown_seconds') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Max attempts</label>
                        <input type="number" wire:model="otp_max_attempts" min="1" max="20" class="field">
                        @error('otp_max_attempts') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Spin --}}
            <div class="border-t border-slate-200 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Spin</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Failsafe lock timeout and the default wheel animation duration used when a spin starts.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Lock timeout (seconds)</label>
                        <input type="number" wire:model="spin_lock_timeout_seconds" min="10" max="600" class="field">
                        <p class="mt-1 text-xs text-slate-500">Stuck spin locks are released after this many seconds.</p>
                        @error('spin_lock_timeout_seconds') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Default animation duration (ms)</label>
                        <input type="number" wire:model="spin_default_duration_ms" min="2000" max="20000" step="500" class="field">
                        <p class="mt-1 text-xs text-slate-500">How long the wheel spins, in milliseconds (2000–20000). Ideally match your spin sound length so audio and animation end together.</p>
                        @error('spin_default_duration_ms') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Branding --}}
            <div class="border-t border-slate-200 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Branding</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Public-facing app name, tagline, and terms &amp; conditions text.
                    </p>
                </div>

                <div>
                    <label class="label">App name</label>
                    <input type="text" wire:model="app_name" class="field">
                    @error('app_name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Tagline</label>
                    <input type="text" wire:model="tagline" class="field" placeholder="Spin to win amazing prizes!">
                    @error('tagline') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Terms &amp; conditions</label>
                    <textarea wire:model="terms" rows="5" class="field"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Shown to players where terms are referenced. Leave blank to hide.</p>
                    @error('terms') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Celebration image confetti --}}
            <div class="border-t border-slate-200 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">Celebration image confetti</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Rain your own uploaded image on every win, on top of the normal confetti (player result + live-view).
                    </p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model="celebration_image_enabled" class="h-4 w-4 rounded accent-brand-500">
                    Enable image confetti
                </label>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Confetti image</label>
                        <div class="flex items-center gap-3">
                            <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg border-2 border-slate-200 bg-slate-50">
                                @if ($celebration_image)
                                    <img src="{{ $celebration_image->temporaryUrl() }}" alt="" class="h-full w-full object-contain">
                                @elseif ($celebration_image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($celebration_image_path) }}" alt="" class="h-full w-full object-contain">
                                @else
                                    <i data-lucide="image" class="h-6 w-6 text-slate-400"></i>
                                @endif
                            </span>
                            <input type="file" wire:model="celebration_image" accept="image/png,image/jpeg,image/webp,image/gif" class="field !py-2 text-xs">
                        </div>
                        <p class="mt-1 text-xs text-slate-500">PNG/JPG/WEBP/GIF, max 2&nbsp;MB. A transparent PNG looks best.</p>
                        <div wire:loading wire:target="celebration_image" class="mt-1 text-xs text-brand-600">Uploading…</div>
                        @error('celebration_image') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Amount</label>
                            <input type="number" wire:model="celebration_image_count" min="5" max="120" class="field">
                            <p class="mt-1 text-xs text-slate-500">Particles per burst.</p>
                            @error('celebration_image_count') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Size (px)</label>
                            <input type="number" wire:model="celebration_image_size" min="16" max="160" class="field">
                            <p class="mt-1 text-xs text-slate-500">On-screen size.</p>
                            @error('celebration_image_size') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary !py-2 text-sm">Save settings</button>
            </div>
        </form>
    </div>
</div>
