<div class="w-full max-w-md">
    <x-journey :current="1" />

    <div class="card">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">Enter to win <i data-lucide="gift" class="inline-block h-6 w-6 align-middle text-cherry-500"></i></h1>
            <p class="mt-2 text-sm text-slate-600">
                Pop in your email and we'll send you a verification code to get started.
            </p>
        </div>

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label class="label" for="email">Email address</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="field"
                    placeholder="you@example.com"
                    autocomplete="email"
                    autofocus
                    inputmode="email"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Send my code</span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Sending…
                </span>
            </button>
        </form>

        <p class="mt-4 text-center text-xs text-slate-500">
            We only use your email to verify you and deliver your prize.
        </p>
    </div>
</div>
