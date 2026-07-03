<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->isAdmin()) {
            $this->redirectRoute('admin.dashboard', navigate: true);
        }
    }

    public function login()
    {
        $this->validate();

        $key = 'admin-login:'.md5($this->email.request()->ip());
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please wait a minute.',
            ]);
        }

        if (! Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::hit($key, decaySeconds: 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        if (! Auth::guard('web')->user()->isAdmin()) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages(['email' => 'This account does not have admin access.']);
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        return $this->redirectRoute('admin.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.login');
    }
}
