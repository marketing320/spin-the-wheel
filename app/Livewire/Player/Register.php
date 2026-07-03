<?php

namespace App\Livewire\Player;

use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Register extends Component
{
    #[Validate('required|email:rfc|max:255')]
    public string $email = '';

    public function mount(): void
    {
        // A fully-registered player skips straight to the spin.
        if (($player = Auth::guard('player')->user()) && $player->hasCompletedForm()) {
            $this->redirectRoute('spin', navigate: true);
        }
    }

    public function submit(OtpService $otp): void
    {
        $this->validate();

        $key = 'otp-request:'.md5(mb_strtolower($this->email).request()->ip());

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many requests. Please wait a minute before trying again.',
            ]);
        }
        RateLimiter::hit($key, decaySeconds: 60);

        $result = $otp->request($this->email, request()->ip());

        if ($result['status'] === 'cooldown') {
            throw ValidationException::withMessages([
                'email' => "Please wait {$result['seconds']} seconds before requesting another code.",
            ]);
        }

        session(['otp_email' => \App\Services\OtpService::normalizeEmail($this->email)]);

        $this->redirectRoute('player.verify-otp', navigate: true);
    }

    public function render()
    {
        return view('livewire.player.register');
    }
}
