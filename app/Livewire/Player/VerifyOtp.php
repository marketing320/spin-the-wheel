<?php

namespace App\Livewire\Player;

use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class VerifyOtp extends Component
{
    public string $email = '';

    public string $code = '';

    public ?string $status = null;

    public function mount(): void
    {
        $this->email = (string) session('otp_email', '');

        if ($this->email === '') {
            $this->redirectRoute('player.register', navigate: true);
        }
    }

    public function verify(OtpService $otp): void
    {
        $this->validate([
            'code' => 'required|digits_between:4,8',
        ]);

        $key = 'otp-verify:'.md5($this->email.request()->ip());
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 10)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please wait a minute and try again.',
            ]);
        }
        RateLimiter::hit($key, decaySeconds: 60);

        $result = $otp->verify($this->email, $this->code);

        match ($result['status']) {
            'verified' => $this->onVerified($result['player']),
            'invalid' => throw ValidationException::withMessages([
                'code' => "Incorrect code. {$result['attempts_left']} attempt(s) remaining.",
            ]),
            'expired' => throw ValidationException::withMessages([
                'code' => 'This code has expired. Please request a new one.',
            ]),
            'locked' => throw ValidationException::withMessages([
                'code' => 'Too many incorrect attempts. Please request a new code.',
            ]),
            default => throw ValidationException::withMessages([
                'code' => 'No active code found. Please request a new one.',
            ]),
        };
    }

    protected function onVerified($player): void
    {
        Auth::guard('player')->login($player, remember: true);
        session()->forget('otp_email');

        if ($player->hasCompletedForm()) {
            $this->redirectRoute('spin', navigate: true);

            return;
        }

        $this->redirectRoute('player.form', navigate: true);
    }

    public function resend(OtpService $otp): void
    {
        $key = 'otp-resend:'.md5($this->email.request()->ip());
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $this->status = 'Please wait before requesting another code.';

            return;
        }
        RateLimiter::hit($key, decaySeconds: 60);

        $result = $otp->request($this->email, request()->ip());

        $this->status = $result['status'] === 'cooldown'
            ? "Please wait {$result['seconds']} seconds before requesting another code."
            : 'A fresh code is on its way to your inbox.';

        $this->reset('code');
    }

    public function render()
    {
        return view('livewire.player.verify-otp');
    }
}
