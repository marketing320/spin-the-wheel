<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\GeofenceSetting;
use App\Models\Player;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinPageModalTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    public function test_geofenced_spin_page_opens_the_location_modal_and_includes_the_error_modal(): void
    {
        $campaign = Campaign::factory()->create();
        $player = Player::factory()->create();
        Prize::factory()->for($campaign)->create();
        GeofenceSetting::create([
            'campaign_id' => $campaign->id,
            'enabled' => true,
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'radius_meters' => 100,
        ]);

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin'));

        $response->assertOk();
        $response->assertSee('"geofenceEnabled":true', escape: false);
        $response->assertSee('id="location-modal"', escape: false);
        $response->assertSee('z-[70] flex items-center justify-center bg-slate-900/50', escape: false);
        $response->assertSee(asset('img/loc_pointer.png'), escape: false);
        $response->assertSee('id="error-modal"', escape: false);
        $response->assertSee('z-[80] hidden items-center justify-center bg-slate-900/50', escape: false);
        $response->assertSee('Try Again');
    }

    public function test_non_geofenced_spin_page_keeps_location_modal_hidden(): void
    {
        $campaign = Campaign::factory()->create();
        $player = Player::factory()->create();
        Prize::factory()->for($campaign)->create();

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin'));

        $response->assertOk();
        $response->assertSee('"geofenceEnabled":false', escape: false);
        $response->assertSee('z-[70] hidden items-center justify-center bg-slate-900/50', escape: false);
    }

    public function test_start_endpoint_returns_the_no_prizes_runtime_error_contract(): void
    {
        Campaign::factory()->create();
        $player = Player::factory()->create();

        $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->postJson(route('spin.start'))
            ->assertConflict()
            ->assertJson([
                'ok' => false,
                'reason' => 'no_prizes',
                'message' => 'No prizes are currently available. Please try again later.',
            ]);
    }
}
