<?php

namespace App\Services;

use App\Mail\OtpCodeMail;
use App\Models\EmailOtp;
use App\Models\Player;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Issues and verifies email one-time passcodes. Codes are hashed at rest,
 * expire after a configurable window, throttle resends, and lock out after too
 * many failed attempts.
 */
class OtpService
{
    public function expiryMinutes(): int
    {
        return (int) Settings::get('otp.expiry_minutes', 10);
    }

    public function resendCooldownSeconds(): int
    {
        return (int) Settings::get('otp.resend_cooldown_seconds', 60);
    }

    public function maxAttempts(): int
    {
        return (int) Settings::get('otp.max_attempts', 5);
    }

    public function codeLength(): int
    {
        return (int) Settings::get('otp.length', 6);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Issue (or reissue) an OTP for the given email.
     *
     * @return array{status: string, seconds?: int, expires_at?: \Illuminate\Support\Carbon}
     */
    public function request(string $email, ?string $ip = null): array
    {
        $email = self::normalizeEmail($email);

        $otp = EmailOtp::where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        // Enforce resend cooldown.
        if ($otp && $otp->resend_available_at && now()->lt($otp->resend_available_at)) {
            return [
                'status' => 'cooldown',
                'seconds' => now()->diffInSeconds($otp->resend_available_at, false),
            ];
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes($this->expiryMinutes());
        $resendAt = now()->addSeconds($this->resendCooldownSeconds());

        $attributes = [
            'otp_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_available_at' => $resendAt,
            'verified_at' => null,
            'request_ip' => $ip,
        ];

        if ($otp) {
            $otp->update($attributes);
        } else {
            EmailOtp::create(array_merge(['email' => $email], $attributes));
        }

        $this->sendCode($email, $code);

        return [
            'status' => 'sent',
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verify a submitted code. On success the matching Player is created /
     * marked verified.
     *
     * @return array{status: string, player?: Player, attempts_left?: int}
     */
    public function verify(string $email, string $code): array
    {
        $email = self::normalizeEmail($email);

        $otp = EmailOtp::where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return ['status' => 'not_found'];
        }

        if ($otp->attempts >= $this->maxAttempts()) {
            return ['status' => 'locked'];
        }

        if ($otp->isExpired()) {
            return ['status' => 'expired'];
        }

        if (! Hash::check($code, $otp->otp_hash)) {
            $otp->increment('attempts');
            $attemptsLeft = max(0, $this->maxAttempts() - $otp->attempts);

            if ($attemptsLeft === 0) {
                return ['status' => 'locked'];
            }

            return ['status' => 'invalid', 'attempts_left' => $attemptsLeft];
        }

        $otp->forceFill(['verified_at' => now()])->save();

        $player = $this->markPlayerVerified($email);

        return ['status' => 'verified', 'player' => $player];
    }

    protected function markPlayerVerified(string $email): Player
    {
        $player = Player::firstOrNew(['email' => $email]);
        $player->otp_verified = true;
        $player->email_verified_at = $player->email_verified_at ?? now();
        $player->save();

        return $player;
    }

    protected function generateCode(): string
    {
        $length = max(4, $this->codeLength());
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    protected function sendCode(string $email, string $code): void
    {
        try {
            Mail::to($email)->send(new OtpCodeMail($code, $this->expiryMinutes()));
        } catch (\Throwable $e) {
            // Never leak SMTP failures to the player flow; log for operators.
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
