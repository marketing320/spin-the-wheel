<div>
    <x-admin.page-header title="Front View Banner"
        subtitle="Configure the full-screen idle slideshow shown on the public /front-view display." />

    <div class="glass card max-w-2xl rounded-2xl p-6">
        <form wire:submit="save" class="space-y-4">
            <label class="flex items-start gap-3 text-sm text-slate-600">
                <input type="checkbox" wire:model="enabled" class="mt-0.5 h-4 w-4 rounded accent-brand-500">
                <span>
                    Show banner slideshow during idle
                    <span class="mt-0.5 block text-xs text-slate-500">
                        While enabled and at least one image is uploaded, /front-view shows this slideshow full-screen whenever nobody is spinning, and automatically switches to the live wheel the instant a spin starts.
                    </span>
                </span>
            </label>
            @error('enabled') <p class="text-sm text-rose-700">{{ $message }}</p> @enderror

            <div>
                <label class="label">Seconds per image</label>
                <input type="number" wire:model="interval_seconds" min="2" max="60" class="field">
                <p class="mt-1 text-xs text-slate-500">How long each image stays on screen before cross-fading to the next (2–60s).</p>
                @error('interval_seconds') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary !py-2 text-sm">Save settings</button>
            </div>
        </form>
    </div>

    <div class="glass mt-5 max-w-2xl rounded-2xl p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">
                Images ({{ count($images) }}/{{ \App\Livewire\Admin\FrontViewBanner::MAX_IMAGES }})
            </h3>
        </div>

        @if (count($images) > 0)
            <div class="mt-4 space-y-3">
                @foreach ($images as $index => $path)
                    <div class="flex items-center gap-3 rounded-xl border-2 border-slate-200 p-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="" class="h-16 w-28 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                        <span class="flex-1 text-xs text-slate-500">Slide {{ $index + 1 }}</span>
                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" wire:click="moveUp({{ $index }})" @disabled($index === 0) class="text-slate-500 hover:text-slate-900 disabled:opacity-30" aria-label="Move up">
                                <i data-lucide="chevron-up" class="h-4 w-4"></i>
                            </button>
                            <button type="button" wire:click="moveDown({{ $index }})" @disabled($index === count($images) - 1) class="text-slate-500 hover:text-slate-900 disabled:opacity-30" aria-label="Move down">
                                <i data-lucide="chevron-down" class="h-4 w-4"></i>
                            </button>
                            <button type="button" wire:click="removeImage({{ $index }})" wire:confirm="Remove this image?" class="ml-2 text-xs font-semibold text-rose-700 hover:text-rose-800">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 text-sm text-slate-500">No images yet — upload one below.</p>
        @endif

        @if (count($images) < \App\Livewire\Admin\FrontViewBanner::MAX_IMAGES)
            <div class="mt-4 border-t border-slate-200 pt-4">
                <label class="label">Add an image</label>
                <input type="file" wire:model="newImage" accept="image/jpeg,image/png,image/webp" class="field">
                <div wire:loading wire:target="newImage" class="mt-1 text-xs text-slate-500">Uploading…</div>
                @error('newImage') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                @if ($newImage)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ $newImage->temporaryUrl() }}" alt="" class="h-16 w-28 rounded-lg object-cover ring-1 ring-slate-200">
                        <button type="button" wire:click="addImage" class="btn-primary !py-2 text-xs">Add to slideshow</button>
                    </div>
                @endif
            </div>
        @else
            <p class="mt-4 text-xs font-semibold text-amber-700">Maximum of {{ \App\Livewire\Admin\FrontViewBanner::MAX_IMAGES }} images reached — remove one to add another.</p>
        @endif
    </div>
</div>
