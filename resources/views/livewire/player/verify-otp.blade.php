<div class="w-full max-w-md">
    <x-journey :current="2" />

    <div class="card">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-extrabold text-white">Check your inbox 📬</h1>
            <p class="mt-2 text-sm text-slate-400">
                We sent a verification code to<br>
                <span class="font-semibold text-slate-200">{{ $email }}</span>
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
            <p class="mt-4 text-center text-sm text-brand-300">{{ $status }}</p>
        @endif

        <div class="mt-6 flex items-center justify-between text-xs text-slate-500">
            <a href="{{ route('player.register') }}" wire:navigate class="hover:text-slate-300">← Change email</a>
            <button type="button" wire:click="resend" wire:loading.attr="disabled" class="font-semibold text-brand-300 hover:text-brand-200">
                Resend code
            </button>
        </div>
    </div>
</div>
