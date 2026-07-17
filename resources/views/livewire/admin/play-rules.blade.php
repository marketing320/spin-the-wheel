<div>
    <x-admin.page-header title="Play Rules" subtitle="Control how often each email can spin.">
        @if ($campaign)
            <button wire:click="create" class="btn-primary !py-2 text-sm">+ New rule</button>
        @endif
    </x-admin.page-header>

    @if (! $campaign)
        <div class="glass rounded-2xl p-8 text-center">
            <p class="text-slate-600">There is no active campaign yet.</p>
            <p class="mt-1 text-sm text-slate-500">Play rules attach to the active campaign, so create and activate one first.</p>
            <a href="{{ route('admin.campaigns') }}" class="btn-primary mt-4 inline-block !py-2 text-sm">Go to campaigns</a>
        </div>
    @else
        <div class="glass mb-5 rounded-2xl p-4">
            <p class="text-sm text-slate-600">
                Rules apply to the active campaign <span class="font-semibold text-slate-900">“{{ $campaign->name }}”</span>.
                Every active rule is enforced together — a player must satisfy <span class="font-semibold text-slate-900">all</span> of them before they can spin.
            </p>
        </div>

        <div class="glass overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Rule type</th>
                            <th class="px-4 py-3">Configuration</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($playRules as $rule)
                            <tr class="hover:bg-slate-100">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ \App\Livewire\Admin\PlayRules::LABELS[$rule->rule_type] ?? $rule->rule_type }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    @switch ($rule->rule_type)
                                        @case (\App\Models\PlayRule::TYPE_EVERY_X_HOURS)
                                            every {{ $rule->cooldown_hours }}h
                                            @break
                                        @case (\App\Models\PlayRule::TYPE_MAX_PER_CAMPAIGN)
                                            max {{ $rule->max_spins_per_campaign }} / campaign
                                            @break
                                        @case (\App\Models\PlayRule::TYPE_MAX_PER_DAY)
                                            max {{ $rule->max_spins_per_day }} / day
                                            @break
                                        @default
                                            —
                                    @endswitch
                                </td>
                                <td class="px-4 py-3">
                                    @if ($rule->is_active)
                                        <span class="pill bg-emerald-50 text-emerald-800 ring-1 ring-emerald-300"><span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span> Active</span>
                                    @else
                                        <span class="pill bg-slate-100 text-slate-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $rule->id }})" class="text-xs font-semibold text-brand-700 hover:text-brand-700">Edit</button>
                                        <button wire:click="delete({{ $rule->id }})"
                                                data-swal-confirm-title="Delete play rule?"
                                                data-swal-confirm="Delete this play rule? This cannot be undone."
                                                data-swal-confirm-button="Delete"
                                                class="text-xs font-semibold text-rose-700 hover:text-rose-700">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No play rules yet. Add one to limit how often players can spin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit play rule' : 'New play rule'">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Rule type</label>
                    <select wire:model.live="rule_type" class="field">
                        @foreach (\App\Livewire\Admin\PlayRules::LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-slate-500">{{ \App\Livewire\Admin\PlayRules::DESCRIPTIONS[$rule_type] ?? '' }}</p>
                    @error('rule_type') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>

                @if ($rule_type === \App\Models\PlayRule::TYPE_EVERY_X_HOURS)
                    <div>
                        <label class="label">Cooldown hours</label>
                        <input type="number" min="1" wire:model="cooldown_hours" class="field" placeholder="e.g. 6">
                        @error('cooldown_hours') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($rule_type === \App\Models\PlayRule::TYPE_MAX_PER_CAMPAIGN)
                    <div>
                        <label class="label">Max spins per campaign</label>
                        <input type="number" min="1" wire:model="max_spins_per_campaign" class="field" placeholder="e.g. 3">
                        @error('max_spins_per_campaign') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($rule_type === \App\Models\PlayRule::TYPE_MAX_PER_DAY)
                    <div>
                        <label class="label">Max spins per day</label>
                        <input type="number" min="1" wire:model="max_spins_per_day" class="field" placeholder="e.g. 2">
                        @error('max_spins_per_day') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded accent-brand-500">
                    Rule is active
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-ghost !py-2 text-sm">Cancel</button>
                    <button type="submit" class="btn-primary !py-2 text-sm">Save rule</button>
                </div>
            </form>
        </x-admin.modal>
    @endif
</div>
