<?php

namespace Tests\Unit;

use App\Mail\OtpCodeMail;
use App\Models\EmailOtp;
use App\Models\Player;
use App\Services\OtpService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OtpService::class);
        Mail::fake();
    }

    public function test_requesting_an_otp_sends_an_email_and_stores_a_hash(): void
    {
        $result = $this->service->request('Player@Example.com');

        $this->assertSame('sent', $result['status']);
        Mail::assertSent(OtpCodeMail::class);
        // Stored lowercased and hashed (not plaintext).
        $otp = EmailOtp::where('email', 'player@example.com')->first();
        $this->assertNotNull($otp);
        $this->assertNotEmpty($otp->otp_hash);
    }

    public function test_verifying_the_correct_code_marks_the_player_verified(): void
    {
        // Seed a known code directly for determinism.
        EmailOtp::create([
            'email' => 'p@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $result = $this->service->verify('p@example.com', '123456');

        $this->assertSame('verified', $result['status']);
        $this->assertInstanceOf(Player::class, $result['player']);
        $this->assertTrue($result['player']->otp_verified);
        $this->assertNotNull($result['player']->email_verified_at);
    }

    public function test_expired_codes_are_rejected(): void
    {
        EmailOtp::create([
            'email' => 'p@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
            'attempts' => 0,
        ]);

        $this->assertSame('expired', $this->service->verify('p@example.com', '123456')['status']);
    }

    public function test_attempt_limit_locks_verification(): void
    {
        Settings::set('otp.max_attempts', 3);

        EmailOtp::create([
            'email' => 'p@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->assertSame('invalid', $this->service->verify('p@example.com', '000000')['status']);
        $this->assertSame('invalid', $this->service->verify('p@example.com', '000000')['status']);
        // Third wrong attempt reaches the limit.
        $this->assertSame('locked', $this->service->verify('p@example.com', '000000')['status']);
        // Even the correct code is now locked out.
        $this->assertSame('locked', $this->service->verify('p@example.com', '123456')['status']);
    }

    public function test_resend_is_throttled_by_cooldown(): void
    {
        Settings::set('otp.resend_cooldown_seconds', 120);

        $this->assertSame('sent', $this->service->request('p@example.com')['status']);
        $this->assertSame('cooldown', $this->service->request('p@example.com')['status']);
    }
}
