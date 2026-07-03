<div class="w-full max-w-md">
    <x-journey :current="2" />

    <div class="card">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">Check your inbox <i data-lucide="mail-open" class="inline-block h-6 w-6 align-middle text-brand-500"></i></h1>
            <p class="mt-2 text-sm text-slate-600">
                We sent a verification code to<br>
                <span class="font-bold text-slate-900">{{ $email }}</span>
            </p>
        </div>

        <form wire:submit="verify" class="space-y-4">
            <div>
                <label class="label" for="code">Verification code</label>
                <input
                    id="code"
                    type="text"
                    wire:model="code"
                    class="field text-center text-2xl font-bold tracking-[0.5em]"
                    placeholder="••••••"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="8"
                    autofocus
                >
                @error('code')
                    <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="verify">Verify & continue</span>
                <span wire:loading wire:target="verify">Verifying…</span>
            </button>
        </form>

        @if ($status)
            <p class="mt-4 text-center text-sm font-semibold text-brand-600">{{ $status }}</p>
        @endif

        <div class="mt-6 flex items-center justify-between text-xs text-slate-500">
            <a href="{{ route('player.register') }}" wire:navigate class="inline-flex items-center gap-1 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Change email</a>
            <button type="button" wire:click="resend" wire:loading.attr="disabled" class="font-bold text-brand-600 hover:text-brand-700">
                Resend code
            </button>
        </div>
    </div>
</div>
