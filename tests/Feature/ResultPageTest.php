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

class ResultPageTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    private function buildSession(Player $player, Prize $prize, Campaign $campaign): SpinSession
    {
        return SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
        ]);
    }

    public function test_result_page_shows_voucher_qr_barcode_and_countdown_when_redeemable(): void
    {
        $player = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['type' => Prize::TYPE_VOUCHER]);
        $session = $this->buildSession($player, $prize, $campaign);

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
            'code' => 'RESULTCODE1',
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->addHours(3),
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin.result', $session));

        $response->assertOk();
        $response->assertSee($voucher->code);
        $response->assertSee('Time remaining');
        $response->assertSee(route('voucher.qr', $voucher->code), escape: false);
        $response->assertSee(route('voucher.barcode', $voucher->code), escape: false);
    }

    public function test_result_page_shows_expired_status_without_qr_or_countdown(): void
    {
        $player = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['type' => Prize::TYPE_VOUCHER]);
        $session = $this->buildSession($player, $prize, $campaign);

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
            'code' => 'RESULTEXPIRED',
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin.result', $session));

        $response->assertOk();
        $response->assertSee('Expired');
        $response->assertDontSee('Time remaining');
    }

    public function test_result_page_without_a_voucher_does_not_render_voucher_block(): void
    {
        $player = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['type' => Prize::TYPE_PHYSICAL]);
        $session = $this->buildSession($player, $prize, $campaign);

        SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin.result', $session));

        $response->assertOk();
        $response->assertDontSee('Time remaining');
        $response->assertSee($prize->name);
    }

    public function test_player_cannot_view_another_players_result(): void
    {
        $owner = Player::factory()->create();
        $intruder = Player::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create();
        $session = $this->buildSession($owner, $prize, $campaign);

        $response = $this->actingAs($intruder, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin.result', $session));

        $response->assertForbidden();
    }
}
