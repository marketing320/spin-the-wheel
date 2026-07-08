<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Prize;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadshowLiveTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_roadshow_live_and_live_view_share_the_same_config_payload(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();
        Settings::setMany([
            'live_view.cta_enabled' => true,
            'live_view.cta_message' => 'Scan to join!',
            'live_view.cta_color' => 'grape',
        ]);

        $extractConfig = function (string $html): array {
            preg_match('/id="live-config">(.*?)<\/script>/s', $html, $matches);

            return json_decode($matches[1], true);
        };

        $liveView = $extractConfig($this->get(route('live-view'))->getContent());
        $roadshow = $extractConfig($this->get(route('roadshow-live'))->getContent());

        $this->assertSame($liveView['segments'], $roadshow['segments']);
        $this->assertSame($liveView['settings'], $roadshow['settings']);
        $this->assertSame($liveView['routes'], $roadshow['routes']);
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
