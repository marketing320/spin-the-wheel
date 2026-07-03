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
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile')
            ->get(route('spin'))
            ->assertRedirect(route('player.register'));
    }

    public function test_desktop_devices_are_blocked_from_all_player_pages(): void
    {
        $desktop = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktop)
            ->get(route('home'))
            ->assertForbidden()
            ->assertViewIs('desktop-blocked');

        $this->withHeader('User-Agent', $desktop)
            ->get(route('player.register'))
            ->assertForbidden()
            ->assertViewIs('desktop-blocked');

        $this->withHeader('User-Agent', $desktop)
            ->post(route('player.logout'))
            ->assertForbidden()
            ->assertViewIs('desktop-blocked');

        $this->withHeader('User-Agent', $desktop)
            ->getJson(route('spin.eligibility'))
            ->assertForbidden();
    }

    public function test_tablets_can_access_player_pages(): void
    {
        $tablet = 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) Mobile/15E148 Safari/604.1';

        $this->withHeader('User-Agent', $tablet)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_admin_routes_require_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }
}
