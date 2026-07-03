<div>
    <x-admin.page-header title="Form Builder" subtitle="Design the registration form players complete before spinning.">
        @if ($campaign)
            <button wire:click="create" class="btn-primary !py-2 text-sm">+ New field</button>
        @endif
    </x-admin.page-header>

    @if (! $campaign)
        <div class="card text-center">
            <p class="text-slate-600">There is no active campaign yet. Activate a campaign before building its registration form.</p>
            <a href="{{ route('admin.campaigns') }}" wire:navigate class="btn-primary mt-4 inline-block !py-2 text-sm">Go to campaigns <i data-lucide="arrow-right" class="inline-block h-4 w-4"></i></a>
        </div>
    @else
        <div class="glass mb-5 rounded-2xl p-4 text-sm text-slate-500">
            These fields appear on the player's <span class="text-slate-800">/player/form</span> page after email verification, and must be completed before spinning.
            Supported types: text, email, phone, number, select, radio, checkbox, date and consent.
            A <span class="text-slate-800">consent</span> field renders as a required agreement checkbox the player must tick.
        </div>

        <div class="glass overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Label</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Required</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($fields as $f)
                            <tr class="hover:bg-slate-100">
                                <td class="px-4 py-3 text-slate-500">{{ $f->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $f->label }}</div>
                                    <div class="text-xs text-slate-500">{{ $f->field_key }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="pill bg-slate-100 text-slate-700">{{ ucfirst($f->field_type) }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $f->is_required ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3">
                                    @if ($f->is_active)
                                        <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300">Active</span>
                                    @else
                                        <span class="pill bg-slate-100 text-slate-500">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $f->id }})" class="text-xs font-semibold text-brand-700 hover:text-brand-700">Edit</button>
                                        <button wire:click="delete({{ $f->id }})" wire:confirm="Delete this field?" class="text-xs font-semibold text-rose-700 hover:text-rose-700">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No fields yet. Add your first field!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit field' : 'New field'">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Label</label>
                    <input type="text" wire:model.live.debounce.400ms="label" class="field" placeholder="e.g. Full name">
                    @error('label') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Field key</label>
                    <input type="text" wire:model="field_key" class="field">
                    <p class="mt-1 text-xs text-slate-500">Stored with the response. Letters, numbers, dashes and underscores only. Must be unique in this campaign.</p>
                    @error('field_key') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Type</label>
                        <select wire:model.live="field_type" class="field">
                            @foreach (\App\Models\FormField::TYPES as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        @error('field_type') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Sort order</label>
                        <input type="number" wire:model="sort_order" class="field">
                        @error('sort_order') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label">Placeholder <span class="text-slate-500">(optional)</span></label>
                    <input type="text" wire:model="placeholder" class="field">
                    @error('placeholder') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                @if (in_array($field_type, \App\Models\FormField::OPTION_TYPES))
                    <div>
                        <label class="label">Options</label>
                        <div class="space-y-2">
                            @foreach ($options as $i => $option)
                                <div wire:key="option-{{ $i }}">
                                    <div class="flex items-center gap-2">
                                        <input type="text" wire:model="options.{{ $i }}.label" class="field" placeholder="Label">
                                        <input type="text" wire:model="options.{{ $i }}.value" class="field" placeholder="Value">
                                        <button type="button" wire:click="removeOption({{ $i }})" class="px-1 text-xl leading-none text-rose-700 hover:text-rose-700"><i data-lucide="x" class="h-5 w-5"></i></button>
                                    </div>
                                    @error("options.{$i}.label") <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                                    @error("options.{$i}.value") <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                        @error('options') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        <button type="button" wire:click="addOption" class="btn-ghost mt-2 !py-1.5 text-xs">+ Add option</button>
                    </div>
                @endif

                <div>
                    <label class="label">Validation rules <span class="text-slate-500">(optional)</span></label>
                    <input type="text" wire:model="validation_rules_input" class="field" placeholder="e.g. min:3,max:20">
                    <p class="mt-1 text-xs text-slate-500">Comma-separated Laravel validation rules applied to this field's response.</p>
                    @error('validation_rules_input') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="is_required" class="h-4 w-4 rounded accent-brand-500">
                        Required
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded accent-brand-500">
                        Active
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                    <button type="submit" class="btn-primary !py-2 text-sm">Save field</button>
                </div>
            </form>
        </x-admin.modal>
    @endif
</div>
