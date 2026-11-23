<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WheelFrameTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    public function test_player_wheel_uses_the_event_frame(): void
    {
        $campaign = Campaign::factory()->create();
        $player = Player::factory()->create();
        Prize::factory()->for($campaign)->create();

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin'));

        $response->assertOk()
            ->assertSee('<body class="player-surface event-theme antialiased spin-event"', false)
            ->assertSee('id="wheel-stage" data-wheel-theme="event"', false)
            ->assertSee('class="spin-event-rim"', false)
            ->assertSee('id="wheel-pointer" class="spin-event-pointer"', false);
    }

    public function test_event_theme_applies_to_public_layouts_and_stays_out_of_admin(): void
    {
        foreach (['home', 'player.register'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('event-theme', false);
        }

        $this->get(route('admin.login'))->assertOk()->assertDontSee('event-theme', false);
    }
}
