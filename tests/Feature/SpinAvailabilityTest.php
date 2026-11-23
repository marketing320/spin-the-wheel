<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Services\SpinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaign = Campaign::factory()->create();
        Prize::factory()->count(3)->for($this->campaign)->create();
    }

    public function test_players_can_spin_directly_after_the_active_spin_and_buffer_end(): void
    {
        $active = app(SpinService::class)->start(Player::factory()->create());
        $player = Player::factory()->create();
        $this->actingAs($player, 'player');

        $this->postJson(route('spin.start'))
            ->assertStatus(423)
            ->assertJsonPath('reason', 'spin_in_progress')
            ->assertJsonMissingPath('queue');

        app(SpinService::class)->complete($active);
        $this->getJson(route('spin.eligibility'))->assertOk()
            ->assertJsonPath('can_start', false)
            ->assertJsonMissingPath('queue');

        $this->travel(20)->seconds();

        $this->getJson(route('spin.eligibility'))->assertOk()
            ->assertJsonPath('can_start', true);
        $this->postJson(route('spin.start'))->assertOk()->assertJsonPath('ok', true);
    }
}
