<div>
    <x-admin.page-header title="Wheel Design"
        subtitle="These settings drive the on-screen 3D wheel on the player and live-view screens.">
    </x-admin.page-header>

    @unless ($campaign)
        <div class="glass rounded-2xl px-6 py-10 text-center">
            <div class="text-3xl"><i data-lucide="ferris-wheel" class="mx-auto h-8 w-8 text-slate-500"></i></div>
            <p class="mt-3 text-slate-600">No active campaign yet. Activate a campaign to configure its wheel.</p>
            <a href="{{ route('admin.campaigns') }}" wire:navigate
                class="btn-primary mt-4 inline-block !py-2 text-sm">Go to Campaigns</a>
        </div>
    @else
        <form wire:submit="save" class="card space-y-8">
            {{-- Appearance --}}
            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Appearance</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Label style</label>
                        <select wire:model="label_style" class="field">
                            <option value="light">Light labels</option>
                            <option value="dark">Dark labels</option>
                        </select>
                        @error('label_style') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Background style</label>
                        <select wire:model="background_style" class="field">
                            <option value="aurora">Aurora</option>
                            <option value="midnight">Midnight</option>
                            <option value="stage">Stage</option>
                        </select>
                        @error('background_style') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Pointer color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" wire:model="pointer_color"
                                class="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-slate-200 bg-transparent">
                            <input type="text" wire:model="pointer_color" class="field" maxlength="9">
                        </div>
                        @error('pointer_color') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Hub logo</label>
                        <input type="text" wire:model="hub_logo" class="field" maxlength="32"
                            placeholder="e.g. brand initials">
                        <p class="mt-1 text-xs text-slate-500">Short text shown at the center of the wheel.</p>
                        @error('hub_logo') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Motion & effects --}}
            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Motion &amp; effects</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label">Spin duration (ms)</label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="8000" max="8000" step="100"
                                wire:model.live="animation_duration_ms" disabled
                                class="h-2 w-full cursor-not-allowed accent-brand-500">
                            <span class="w-16 shrink-0 text-right text-sm font-semibold text-slate-900">{{ $animation_duration_ms }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Fixed at 8 seconds to stay synchronized across all screens.</p>
                        @error('animation_duration_ms') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Glow intensity</label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="1"
                                wire:model.live="glow_intensity"
                                class="h-2 w-full cursor-pointer accent-brand-500">
                            <span class="w-16 shrink-0 text-right text-sm font-semibold text-slate-900">{{ $glow_intensity }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Neon glow around the wheel segments (0–100).</p>
                        @error('glow_intensity') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Three.js intensity</label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" step="1"
                                wire:model.live="three_intensity"
                                class="h-2 w-full cursor-pointer accent-brand-500">
                            <span class="w-16 shrink-0 text-right text-sm font-semibold text-slate-900">{{ $three_intensity }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Lighting, particles and 3D richness (0–100).</p>
                        @error('three_intensity') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" wire:model="sound_enabled"
                                class="h-4 w-4 rounded accent-brand-500">
                            Enable spin sound effects
                        </label>
                        @error('sound_enabled') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-5">
                <button type="submit" class="btn-primary !py-2 text-sm">Save wheel design</button>
            </div>
        </form>
    @endunless
</div>
