@props(['show' => false, 'title' => ''])
@if ($show)
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 p-4 backdrop-blur">
        <div class="card my-8 w-full max-w-lg">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
                <button type="button" wire:click="$set('showModal', false)" class="text-2xl leading-none text-slate-400 hover:text-white">&times;</button>
            </div>
            {{ $slot }}
        </div>
    </div>
@endif
