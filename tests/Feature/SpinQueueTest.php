<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Services\SpinQueueService;
use App\Services\SpinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinQueueTest extends TestCase
{
    use RefreshDatabase;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaign = Campaign::factory()->create();
        Prize::factory()->count(3)->for($this->campaign)->create();
    }

    public function test_players_join_in_fifo_order_and_live_view_uses_full_names(): void
    {
        $activePlayer = Player::factory()->create(['display_name' => 'Active Player']);
        $first = Player::factory()->create(['display_name' => 'First Waiting']);
        $second = Player::factory()->create(['display_name' => 'Second Waiting']);
        app(SpinService::class)->start($activePlayer);

        $mobile = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';
        $this->actingAs($first, 'player')->withHeader('User-Agent', $mobile)
            ->postJson(route('spin.queue'))
            ->assertStatus(202)
            ->assertJsonPath('queue.position', 1)
            ->assertJsonPath('queue.ahead', 0);

        $this->actingAs($second, 'player')->withHeader('User-Agent', $mobile)
            ->postJson(route('spin.queue'))
            ->assertStatus(202)
            ->assertJsonPath('queue.position', 2)
            ->assertJsonPath('queue.ahead', 1);

        $snapshot = app(SpinQueueService::class)->snapshot($this->campaign);
        $this->assertSame(['First Waiting', 'Second Waiting'], array_column($snapshot['players'], 'name'));
    }

    public function test_first_queued_player_can_start_only_after_the_buffer(): void
    {
        $active = app(SpinService::class)->start(Player::factory()->create());
        $waiting = Player::factory()->create();
        $queue = app(SpinQueueService::class);
        $queue->join($this->campaign, $waiting);
        app(SpinService::class)->complete($active);

        $mobile = 'Mozilla/5.0 (Android 15; Mobile) AppleWebKit/537.36';
        $this->actingAs($waiting, 'player')->withHeader('User-Agent', $mobile)
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('can_start', false);

        $this->travel(16)->seconds();

        $this->actingAs($waiting, 'player')->withHeader('User-Agent', $mobile)
            ->getJson(route('spin.eligibility'))
            ->assertOk()
            ->assertJsonPath('queue.position', 1)
            ->assertJsonPath('can_start', true);
    }
}
