<div>
    <x-admin.page-header title="Geofence" subtitle="Restrict spins to players physically at your event location." />

    @if (! $campaign)
        <div class="glass rounded-2xl p-8 text-center">
            <div class="text-3xl"><i data-lucide="map-pin" class="mx-auto h-8 w-8 text-slate-500"></i></div>
            <h3 class="mt-3 text-lg font-bold text-slate-900">No active campaign</h3>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                Geofence settings apply to the currently active campaign. Activate a campaign first, then come back to configure the location fence.
            </p>
            <a href="{{ route('admin.campaigns') }}" wire:navigate class="btn-primary mt-5 inline-block !py-2 text-sm">Go to campaigns</a>
        </div>
    @else
        <form wire:submit="save" class="glass max-w-2xl space-y-5 rounded-2xl p-6">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-500">Active campaign</div>
                <div class="text-base font-semibold text-slate-900">{{ $campaign->name }}</div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="enabled" class="h-4 w-4 rounded accent-brand-500">
                Enable geofence for this campaign
            </label>

            <div>
                <label class="label">Location name <span class="text-slate-500">(optional)</span></label>
                <input type="text" wire:model="location_name" placeholder="e.g. Main Hall, Downtown Branch" class="field">
                @error('location_name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Latitude</label>
                    <input type="number" step="any" wire:model="latitude" placeholder="-90 to 90" class="field">
                    @error('latitude') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Longitude</label>
                    <input type="number" step="any" wire:model="longitude" placeholder="-180 to 180" class="field">
                    @error('longitude') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div x-data="{
                supported: 'geolocation' in navigator,
                status: '',
                locate() {
                    if (! this.supported) { this.status = 'Geolocation is not supported by this browser.'; return; }
                    this.status = 'Locating…';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            $wire.set('latitude', pos.coords.latitude);
                            $wire.set('longitude', pos.coords.longitude);
                            this.status = 'Location filled in from your device.';
                        },
                        (err) => { this.status = 'Could not get location: ' + err.message; }
                    );
                }
            }">
                <button type="button" x-on:click="locate()" x-bind:disabled="! supported" class="btn-ghost !py-2 text-sm disabled:opacity-50">
                    <i data-lucide="map-pin" class="inline-block h-4 w-4"></i> Use my current location
                </button>
                <p class="mt-2 text-xs text-slate-500" x-text="status" x-show="status" x-cloak></p>
            </div>

            <div>
                <label class="label">Radius (meters)</label>
                <input type="number" min="1" step="1" wire:model="radius_meters" class="field">
                @error('radius_meters') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Blocked message</label>
                <textarea wire:model="blocked_message" rows="3" class="field" placeholder="Shown to players who are outside the allowed area."></textarea>
                @error('blocked_message') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-xs text-slate-500">
                Distance is validated server-side using the Haversine formula against the coordinates above.
                Players whose device location falls outside the radius — or who deny the location permission — are blocked from spinning and shown the message above.
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit" class="btn-primary !py-2 text-sm">Save settings</button>
            </div>
        </form>
    @endif
</div>
