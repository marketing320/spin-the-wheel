<?php

namespace Tests\Feature;

use App\Exceptions\SpinException;
use App\Models\Campaign;
use App\Models\GeofenceSetting;
use App\Models\Player;
use App\Models\PlayRule;
use App\Models\Prize;
use App\Models\SpinSession;
use App\Services\SpinLockService;
use App\Services\SpinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinFlowTest extends TestCase
{
    use RefreshDatabase;

    private Campaign $campaign;

    private SpinService $spins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaign = Campaign::factory()->create();
        Prize::factory()->count(3)->for($this->campaign)->sequence(
            ['name' => 'A', 'weight' => 10],
            ['name' => 'B', 'weight' => 5],
            ['name' => 'C', 'weight' => 1],
        )->create();
        $this->spins = app(SpinService::class);
    }

    private function readyPlayer(): Player
    {
        return Player::factory()->create();
    }

    public function test_unverified_player_cannot_spin(): void
    {
        $player = Player::factory()->unverified()->create();

        $this->expectException(SpinException::class);
        $this->spins->start($player);
    }

    public function test_player_without_completed_form_cannot_spin(): void
    {
        $player = Player::factory()->formIncomplete()->create();

        try {
            $this->spins->start($player);
            $this->fail('Expected SpinException');
        } catch (SpinException $e) {
            $this->assertSame('not_eligible', $e->reason);
        }
    }

    public function test_blocked_player_cannot_spin(): void
    {
        $player = Player::factory()->create(['blocked_at' => now()]);

        try {
            $this->spins->start($player);
            $this->fail('Blocked player should not be able to spin');
        } catch (SpinException $e) {
            $this->assertSame('not_eligible', $e->reason);
        }
    }

    public function test_prize_is_selected_server_side(): void
    {
        $session = $this->spins->start($this->readyPlayer());

        $this->assertNotNull($session->prize_id);
        $this->assertTrue($this->campaign->prizes->pluck('id')->contains($session->prize_id));
        $this->assertNotEmpty($session->metadata['segments']);
        $this->assertSame(SpinSession::STATUS_ACTIVE, $session->status);
        $this->assertSame(8000, $session->spin_duration_ms);
        $this->assertEquals(8000, $session->started_at->diffInMilliseconds($session->ends_at));
        $this->assertEquals(7000, $session->ends_at->diffInMilliseconds($session->buffer_ends_at));
        $this->assertSame(11000, $this->spins->buildStartedPayload($session)['sound_duration_ms']);
    }

    public function test_only_one_player_can_spin_at_a_time(): void
    {
        $this->spins->start($this->readyPlayer());

        try {
            $this->spins->start($this->readyPlayer());
            $this->fail('Second concurrent spin should be blocked');
        } catch (SpinException $e) {
            $this->assertSame('spin_in_progress', $e->reason);
        }

        $this->assertSame(1, SpinSession::whereNotNull('active_guard')->count());
    }

    public function test_once_per_campaign_rule_blocks_a_second_spin(): void
    {
        PlayRule::create([
            'campaign_id' => $this->campaign->id,
            'rule_type' => PlayRule::TYPE_ONCE_PER_CAMPAIGN,
            'is_active' => true,
        ]);

        $player = $this->readyPlayer();
        $session = $this->spins->start($player);
        $this->spins->complete($session);

        try {
            $this->spins->start($player);
            $this->fail('Second spin should be blocked by once_per_campaign');
        } catch (SpinException $e) {
            $this->assertSame('not_eligible', $e->reason);
        }
    }

    public function test_completing_a_spin_records_a_result_and_keeps_the_lock_through_the_buffer(): void
    {
        $session = $this->spins->start($this->readyPlayer());
        $this->spins->complete($session);

        $session->refresh();
        $this->assertSame(SpinSession::STATUS_ACTIVE, $session->status);
        $this->assertSame(SpinSession::GUARD_ON, $session->active_guard);
        $this->assertDatabaseHas('spin_results', ['spin_session_id' => $session->id]);

        $this->travel(16)->seconds();
        app(SpinLockService::class)->currentActive();

        $session->refresh();
        $this->assertSame(SpinSession::STATUS_COMPLETED, $session->status);
        $this->assertNull($session->active_guard);
        $this->assertSame(0, SpinSession::whereNotNull('active_guard')->count());
    }

    public function test_another_player_cannot_spin_during_the_seven_second_buffer(): void
    {
        $session = $this->spins->start($this->readyPlayer());
        $this->spins->complete($session);

        $this->travel(14)->seconds();

        try {
            $this->spins->start($this->readyPlayer());
            $this->fail('The global lock should remain held during the result buffer.');
        } catch (SpinException $e) {
            $this->assertSame('spin_in_progress', $e->reason);
        }

        $this->travel(2)->seconds();
        $next = $this->spins->start($this->readyPlayer());
        $this->assertSame(SpinSession::STATUS_ACTIVE, $next->status);
    }

    public function test_player_outside_geofence_cannot_spin(): void
    {
        GeofenceSetting::create([
            'campaign_id' => $this->campaign->id,
            'enabled' => true,
            'latitude' => 3.0,
            'longitude' => 101.0,
            'radius_meters' => 100,
        ]);

        try {
            $this->spins->start($this->readyPlayer(), ['lat' => 3.5, 'lng' => 101.5]);
            $this->fail('Spin should be blocked outside the geofence');
        } catch (SpinException $e) {
            $this->assertSame('geofence_blocked', $e->reason);
        }
    }

    public function test_player_inside_geofence_can_spin(): void
    {
        GeofenceSetting::create([
            'campaign_id' => $this->campaign->id,
            'enabled' => true,
            'latitude' => 3.0,
            'longitude' => 101.0,
            'radius_meters' => 500,
        ]);

        $session = $this->spins->start($this->readyPlayer(), ['lat' => 3.0002, 'lng' => 101.0002]);
        $this->assertSame(SpinSession::STATUS_ACTIVE, $session->status);
    }

    public function test_stuck_spin_lock_expires_safely(): void
    {
        // Simulate a stuck active spin whose failsafe window has elapsed.
        SpinSession::create([
            'campaign_id' => $this->campaign->id,
            'player_id' => $this->readyPlayer()->id,
            'status' => SpinSession::STATUS_ACTIVE,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->subMinutes(4),
            'expires_at' => now()->subMinute(),
            'active_guard' => SpinSession::GUARD_ON,
        ]);

        $released = app(SpinLockService::class)->expireStale();
        $this->assertSame(1, $released);
        $this->assertSame(0, SpinSession::whereNotNull('active_guard')->count());

        // A new spin can now proceed.
        $session = $this->spins->start($this->readyPlayer());
        $this->assertSame(SpinSession::STATUS_ACTIVE, $session->status);
    }

    public function test_live_view_can_fetch_the_active_spin(): void
    {
        $session = $this->spins->start($this->readyPlayer());

        $response = $this->getJson(route('live-view.active'));

        $response->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('spin.spin_session_id', $session->id);
    }

    public function test_live_view_reports_idle_when_no_active_spin(): void
    {
        $this->getJson(route('live-view.active'))
            ->assertOk()
            ->assertJsonPath('active', false);
    }
}
