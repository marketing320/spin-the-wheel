<div>
    <x-admin.page-header title="Live View"
        subtitle="Control what the public /live-view screen shows during and between spins." />

    <div class="glass card max-w-2xl rounded-2xl p-6">
        <form wire:submit="save" class="space-y-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Player privacy</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Decide how much of the current player's identity appears on the big screen.
                    </p>
                </div>

                <label class="flex items-start gap-3 text-sm text-slate-300">
                    <input type="checkbox" wire:model="show_player_name" class="mt-0.5 h-4 w-4 rounded accent-brand-500">
                    <span>
                        Show player display name
                        <span class="mt-0.5 block text-xs text-slate-500">
                            When enabled, the spinning player's chosen display name is shown on the live screen.
                        </span>
                    </span>
                </label>
                @error('show_player_name') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror

                <label class="flex items-start gap-3 text-sm text-slate-300">
                    <input type="checkbox" wire:model="show_masked_email" class="mt-0.5 h-4 w-4 rounded accent-brand-500">
                    <span>
                        Show masked email only
                        <span class="mt-0.5 block text-xs text-slate-500">
                            Protects privacy by revealing only a masked address such as
                            <code class="rounded bg-white/5 px-1 text-slate-300">j****@example.com</code>
                            instead of the full email.
                        </span>
                    </span>
                </label>
                @error('show_masked_email') <p class="text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-white/10 pt-6 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Idle & branding</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Shown on the live screen when nobody is spinning.
                    </p>
                </div>

                <div>
                    <label class="label">Idle message</label>
                    <input type="text" wire:model="idle_message" class="field" placeholder="Waiting for the next lucky player…">
                    <p class="mt-1 text-xs text-slate-500">Displayed on the idle screen between spins.</p>
                    @error('idle_message') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Screen branding</label>
                    <input type="text" wire:model="branding" class="field" placeholder="{{ config('app.name') }}">
                    <p class="mt-1 text-xs text-slate-500">Brand name or heading shown on the live display.</p>
                    @error('branding') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Auto-reset to idle (seconds)</label>
                    <input type="number" wire:model="auto_reset_seconds" min="3" max="120" class="field">
                    <p class="mt-1 text-xs text-slate-500">How long the result stays on screen before returning to the idle screen (3–120s).</p>
                    @error('auto_reset_seconds') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary !py-2 text-sm">Save live view settings</button>
            </div>
        </form>
    </div>
</div>
