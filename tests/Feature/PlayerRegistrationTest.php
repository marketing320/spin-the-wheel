<?php

namespace Tests\Feature;

use App\Livewire\Player\Register;
use App\Livewire\Player\VerifyOtp;
use App\Mail\OtpCodeMail;
use App\Models\EmailOtp;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_sends_an_otp_and_advances_to_verification(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('email', 'newplayer@example.com')
            ->call('submit')
            ->assertRedirect(route('player.verify-otp'));

        Mail::assertSent(OtpCodeMail::class);
        $this->assertDatabaseHas('email_otps', ['email' => 'newplayer@example.com']);
    }

    public function test_invalid_email_is_rejected(): void
    {
        Livewire::test(Register::class)
            ->set('email', 'not-an-email')
            ->call('submit')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_verifying_a_correct_code_logs_the_player_in(): void
    {
        EmailOtp::create([
            'email' => 'player@example.com',
            'otp_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // VerifyOtp reads the pending email from the session in mount().
        session(['otp_email' => 'player@example.com']);

        Livewire::test(VerifyOtp::class)
            ->set('code', '654321')
            ->call('verify')
            ->assertRedirect(route('player.form'));

        $this->assertAuthenticatedAs(Player::where('email', 'player@example.com')->first(), 'player');
    }

    public function test_spin_page_requires_an_authenticated_player(): void
    {
        $this->get(route('spin'))->assertRedirect(route('player.register'));
    }

    public function test_admin_routes_require_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }
}
