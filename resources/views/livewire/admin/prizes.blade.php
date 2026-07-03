<div>
    <x-admin.page-header title="Prizes" subtitle="Configure the prizes and odds for your active campaign.">
        @if ($campaign)
            <button wire:click="create" class="btn-primary !py-2 text-sm">+ New prize</button>
        @endif
    </x-admin.page-header>

    @if (! $campaign)
        <div class="glass rounded-2xl p-8 text-center">
            <p class="text-slate-600">No active campaign. Activate one first.</p>
            <a href="{{ route('admin.campaigns') }}" class="mt-4 inline-block btn-primary !py-2 text-sm">Go to campaigns</a>
        </div>
    @else
        {{-- Probability / configuration panel --}}
        <div class="glass mb-5 rounded-2xl p-4">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="pill bg-slate-100 text-slate-700">Mode: {{ ucfirst($campaign->prize_mode) }}</span>
                @if ($campaign->prize_mode === \App\Models\Campaign::MODE_STRICT)
                    <span class="pill bg-brand-50 text-brand-700 ring-1 ring-brand-300">Total: {{ number_format($config['total_percentage'], 2) }}%</span>
                @else
                    <span class="pill bg-brand-50 text-brand-700 ring-1 ring-brand-300">Total weight: {{ $config['total_weight'] }}</span>
                @endif
            </div>

            @if (! empty($config['warnings']))
                <div class="mt-3 space-y-2">
                    @foreach ($config['warnings'] as $warning)
                        <div class="flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800 ring-1 ring-amber-300">
                            <i data-lucide="triangle-alert" class="h-4 w-4 shrink-0"></i>
                            <span>{{ $warning }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800 ring-1 ring-emerald-300">
                    <i data-lucide="check" class="h-4 w-4 shrink-0"></i>
                    <span>Configuration looks good.</span>
                </div>
            @endif
        </div>

        <div class="glass overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Color</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Rarity</th>
                            <th class="px-4 py-3">Odds</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($prizes as $prize)
                            <tr class="hover:bg-slate-100">
                                <td class="px-4 py-3">
                                    <div class="h-6 w-6 rounded-full ring-1 ring-slate-200" style="background-color: {{ $prize->displayColor() }}"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($prize->imageUrl())
                                            <img src="{{ $prize->imageUrl() }}" alt="" class="h-9 w-9 rounded-lg object-cover ring-1 ring-slate-200">
                                        @endif
                                        <div class="font-semibold text-slate-900">{{ $prize->name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-rarity-badge :rarity="$prize->rarity" /></td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($campaign->prize_mode === \App\Models\Campaign::MODE_STRICT)
                                        {{ $prize->win_percentage !== null ? rtrim(rtrim(number_format((float) $prize->win_percentage, 2), '0'), '.') . '%' : '—' }}
                                    @else
                                        w:{{ $prize->weight ?? 0 }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($prize->inventory_enabled)
                                        {{ $prize->inventory_quantity ?? 0 }}
                                    @else
                                        ∞
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($prize->is_active)
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400" title="Active"></span>
                                    @else
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-slate-500" title="Inactive"></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $prize->id }})" class="text-xs font-semibold text-brand-700 hover:text-brand-600">Edit</button>
                                        <button wire:click="delete({{ $prize->id }})" wire:confirm="Delete this prize?" class="text-xs font-semibold text-rose-700 hover:text-rose-800">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No prizes yet. Add your first one!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit prize' : 'New prize'">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model="name" class="field">
                @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Description</label>
                <textarea wire:model="description" rows="2" class="field"></textarea>
                @error('description') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Rarity</label>
                    <select wire:model="rarity" class="field">
                        @foreach (\App\Models\Prize::RARITIES as $r)
                            <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                    @error('rarity') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Confetti level</label>
                    <select wire:model="confetti_level" class="field">
                        @foreach (\App\Models\Prize::CONFETTI_LEVELS as $c)
                            <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                    @error('confetti_level') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Segment color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model="color" class="h-10 w-14 cursor-pointer rounded-lg bg-transparent">
                        <input type="text" wire:model="color" class="field" placeholder="#6366f1">
                    </div>
                    @error('color') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Sort order</label>
                    <input type="number" wire:model="sort_order" class="field">
                    @error('sort_order') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Win percentage (strict mode)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="win_percentage" class="field" placeholder="0.00">
                    @error('win_percentage') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Weight (weighted mode)</label>
                    <input type="number" min="0" wire:model="weight" class="field" placeholder="0">
                    @error('weight') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model.live="inventory_enabled" class="h-4 w-4 rounded accent-brand-500">
                    Track inventory for this prize
                </label>
                @if ($inventory_enabled)
                    <div class="mt-2">
                        <label class="label">Inventory quantity</label>
                        <input type="number" min="0" wire:model="inventory_quantity" class="field" placeholder="0">
                        @error('inventory_quantity') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div>
                <label class="label">Redemption message</label>
                <textarea wire:model="redemption_message" rows="2" class="field" placeholder="Shown to the player after they win."></textarea>
                @error('redemption_message') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label">Prize image</label>
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="" class="mb-2 h-16 w-16 rounded-lg object-cover ring-1 ring-slate-200">
                @elseif ($existingImagePath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" alt="" class="mb-2 h-16 w-16 rounded-lg object-cover ring-1 ring-slate-200">
                @endif
                <input type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="field">
                <div wire:loading wire:target="image" class="mt-1 text-xs text-slate-500">Uploading…</div>
                @error('image') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded accent-brand-500">
                Prize is active (can be won)
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                <button type="submit" class="btn-primary !py-2 text-sm">Save prize</button>
            </div>
        </form>
    </x-admin.modal>
</div>
