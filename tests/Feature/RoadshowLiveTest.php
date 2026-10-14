<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadshowLiveTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    public function test_roadshow_live_is_publicly_accessible_and_renders_the_wheel(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();

        $response = $this->get(route('roadshow-live'));

        $response->assertOk();
        $response->assertSee('id="live-config"', false);
        $response->assertSee('id="wheel-stage"', false);
        $response->assertSee('id="queue-list"', false);
        $response->assertSee('id="prize-reveal"', false);
    }

    public function test_live_view_shows_a_leaderboard_while_roadshow_keeps_the_wheel(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        Settings::setMany([
            'live_view.cta_enabled' => true,
            'live_view.cta_message' => 'Scan to join!',
            'live_view.cta_color' => 'grape',
        ]);

        $liveView = $this->withHeader('User-Agent', self::MOBILE_UA)->get(route('live-view'));
        $roadshow = $this->get(route('roadshow-live'));

        $liveView->assertOk();
        $liveView->assertSee('Latest winners');
        $liveView->assertSee('id="leaderboard-list"', false);
        $liveView->assertDontSee('id="wheel-stage"', false);

        $roadshow->assertOk();
        $roadshow->assertSee('id="wheel-stage"', false);
    }

    public function test_live_view_shows_recent_wins_in_time_order_across_rarities_and_honors_name_privacy(): void
    {
        $otherCampaign = Campaign::factory()->create();
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['name' => 'Grand prize', 'rarity' => 'legendary']);
        $otherPrize = Prize::factory()->for($campaign)->create(['name' => 'Everyday prize', 'rarity' => 'common']);
        $otherCampaignPrize = Prize::factory()->for($otherCampaign)->create();
        $winner = Player::factory()->create(['display_name' => 'Leaderboard Winner']);
        $runnerUp = Player::factory()->create(['display_name' => 'Runner Up']);
        $unrelatedWinner = Player::factory()->create(['display_name' => 'Other Campaign Winner']);

        $this->recordWin($campaign, $winner, $otherPrize, now()->subMinutes(20));
        $this->recordWin($campaign, $winner, $prize, now()->subMinutes(2));
        $this->recordWin($campaign, $runnerUp, $otherPrize, now()->subMinute());
        $this->recordWin($otherCampaign, $unrelatedWinner, $otherCampaignPrize, now());

        Settings::setMany([
            'live_view.show_player_name' => false,
            'live_view.show_masked_email' => true,
        ]);

        $response = $this->get(route('live-view.leaderboard'));

        $response->assertOk();
        $response->assertJsonPath('winners.0.name', $runnerUp->maskedEmail());
        $response->assertJsonPath('winners.0.prize', 'Everyday prize');
        $response->assertJsonPath('winners.0.rarity', 'common');
        $response->assertJsonPath('winners.1.name', $winner->maskedEmail());
        $response->assertJsonPath('winners.1.prize', 'Grand prize');
        $response->assertJsonPath('winners.1.rarity', 'legendary');
        $response->assertJsonCount(3, 'winners');
        $response->assertJsonMissing(['name' => 'Other Campaign Winner']);
        $response->assertHeader('Cache-Control', 'no-store, private');
    }

    private function recordWin(Campaign $campaign, Player $player, Prize $prize, \Illuminate\Support\Carbon $wonAt): void
    {
        $session = SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => \App\Models\SpinSession::STATUS_ACTIVE,
            'completed_at' => $wonAt,
        ]);

        SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);
    }

    public function test_roadshow_live_hides_the_cta_banner_when_disabled(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        Settings::setMany(['live_view.cta_enabled' => false]);

        $response = $this->get(route('roadshow-live'));

        $response->assertOk();
        $response->assertDontSee('id="cta-banner"', false);
    }
}
