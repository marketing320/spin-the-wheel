<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PastPrizesPageTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    public function test_page_renders_with_a_redeemable_voucher_and_its_countdown(): void
    {
        $player = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['type' => Prize::TYPE_VOUCHER]);

        $session = SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
        ]);

        $result = SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);

        $voucher = Voucher::create([
            'spin_result_id' => $result->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'code' => 'TESTCODE01',
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->addHours(3),
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('player.prizes'));

        $response->assertOk();
        $response->assertSee($voucher->code);
        $response->assertSee('Time remaining');
        $response->assertSee(route('voucher.qr', $voucher->code), escape: false);
        $response->assertSee(route('voucher.barcode', $voucher->code), escape: false);
    }

    public function test_expired_voucher_does_not_show_qr_or_countdown(): void
    {
        $player = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['type' => Prize::TYPE_VOUCHER]);

        $session = SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
        ]);

        $result = SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);

        Voucher::create([
            'spin_result_id' => $result->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'code' => 'EXPIREDCODE',
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('player.prizes'));

        $response->assertOk();
        $response->assertSee('Expired');
        $response->assertDontSee('Time remaining');
    }

    public function test_player_cannot_see_another_players_prizes(): void
    {
        $player = Player::factory()->create();
        Player::factory()->create(); // someone else's history, must not leak

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('player.prizes'));

        $response->assertOk();
        $response->assertSee("You haven't won anything yet", escape: false);
    }
}
