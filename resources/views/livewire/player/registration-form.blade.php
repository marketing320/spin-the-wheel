<div class="w-full max-w-lg">
    <x-journey :current="3" />

    <div class="card">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-extrabold text-white">Almost there! ✍️</h1>
            <p class="mt-2 text-sm text-slate-400">Complete your details to unlock the wheel.</p>
        </div>

        @if ($fields->isEmpty())
            <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center text-sm text-slate-300">
                No additional details are required. You're ready to spin!
            </div>
            <a href="{{ route('spin') }}" wire:navigate class="btn-primary mt-6 w-full">Continue to the wheel →</a>
        @else
            <form wire:submit="submit" class="space-y-5">
                @foreach ($fields as $field)
                    @php
                        $key = $field->field_key;
                        $options = collect($field->options ?? [])->map(fn ($o) => is_array($o)
                            ? ['label' => $o['label'] ?? $o['value'] ?? '', 'value' => (string) ($o['value'] ?? $o['label'] ?? '')]
                            : ['label' => (string) $o, 'value' => (string) $o]);
                    @endphp

                    <div>
                        @unless ($field->field_type === 'consent')
                            <label class="label" for="f-{{ $key }}">
                                {{ $field->label }}
                                @if ($field->is_required)<span class="text-rose-400">*</span>@endif
                            </label>
                        @endunless

                        @switch($field->field_type)
                            @case('select')
                                <select id="f-{{ $key }}" wire:model="responses.{{ $key }}" class="field">
                                    <option value="">{{ $field->placeholder ?: 'Select an option' }}</option>
                                    @foreach ($options as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('radio')
                                <div class="space-y-2">
                                    @foreach ($options as $opt)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-900/40 px-4 py-3 text-sm hover:border-brand-400/50">
                                            <input type="radio" wire:model="responses.{{ $key }}" value="{{ $opt['value'] }}" class="h-4 w-4 accent-brand-500">
                                            <span>{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @case('checkbox')
                                <div class="space-y-2">
                                    @foreach ($options as $opt)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-slate-900/40 px-4 py-3 text-sm hover:border-brand-400/50">
                                            <input type="checkbox" wire:model="responses.{{ $key }}" value="{{ $opt['value'] }}" class="h-4 w-4 rounded accent-brand-500">
                                            <span>{{ $opt['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @case('consent')
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 bg-slate-900/40 px-4 py-3 text-sm">
                                    <input type="checkbox" wire:model="responses.{{ $key }}" class="mt-0.5 h-4 w-4 rounded accent-brand-500">
                                    <span>{{ $field->label }} @if ($field->is_required)<span class="text-rose-400">*</span>@endif</span>
                                </label>
                                @break

                            @case('date')
                                <input id="f-{{ $key }}" type="date" wire:model="responses.{{ $key }}" class="field">
                                @break

                            @case('number')
                                <input id="f-{{ $key }}" type="number" wire:model="responses.{{ $key }}" class="field" placeholder="{{ $field->placeholder }}">
                                @break

                            @case('email')
                                <input id="f-{{ $key }}" type="email" wire:model="responses.{{ $key }}" class="field" placeholder="{{ $field->placeholder }}" inputmode="email">
                                @break

                            @case('phone')
                                <input id="f-{{ $key }}" type="tel" wire:model="responses.{{ $key }}" class="field" placeholder="{{ $field->placeholder }}" inputmode="tel">
                                @break

                            @default
                                <input id="f-{{ $key }}" type="text" wire:model="responses.{{ $key }}" class="field" placeholder="{{ $field->placeholder }}">
                        @endswitch

                        @error("responses.{$key}")
                            <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                        @error("responses.{$key}.*")
                            <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Save & continue to the wheel →</span>
                    <span wire:loading wire:target="submit">Saving…</span>
                </button>
            </form>
        @endif
    </div>
</div>
