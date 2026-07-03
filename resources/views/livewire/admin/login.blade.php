<div class="w-full max-w-sm">
    <div class="mb-6 text-center">
        <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-pink-500 text-2xl shadow-lg">🎡</div>
        <h1 class="mt-4 text-2xl font-extrabold text-white">Admin sign in</h1>
        <p class="mt-1 text-sm text-slate-400">{{ config('app.name') }} control panel</p>
    </div>

    <div class="card">
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" wire:model="email" class="field" autofocus autocomplete="username">
                @error('email') <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" type="password" wire:model="password" class="field" autocomplete="current-password">
                @error('password') <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded accent-brand-500">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">Signing in…</span>
            </button>
        </form>
    </div>
</div>
