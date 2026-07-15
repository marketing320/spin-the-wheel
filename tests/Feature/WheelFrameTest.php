<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Services\WheelAnimationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WheelFrameTest extends TestCase
{
    use RefreshDatabase;

    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile';

    public function test_player_wheel_uses_the_stationary_ring_frame_without_an_outer_bezel(): void
    {
        $campaign = Campaign::factory()->create();
        $player = Player::factory()->create();
        Prize::factory()->for($campaign)->create();

        $response = $this->actingAs($player, 'player')
            ->withHeader('User-Agent', self::MOBILE_UA)
            ->get(route('spin'));

        $this->assertFramedWheel($response->getContent());
    }

    public function test_public_live_wheel_layouts_use_the_same_stationary_frame_layers(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->create();

        $this->assertFramedWheel($this->get(route('front-view'))->getContent());
        $this->assertFramedWheel($this->get(route('roadshow-live'))->getContent());

        $segments = app(WheelAnimationService::class)->segments($campaign);
        $desktopLiveView = $this->view('live-view', [
            'campaign' => $campaign,
            'segments' => $segments,
            'settings' => [
                'idle_message' => '',
                'branding' => '',
                'auto_reset_seconds' => 12,
                'cta_enabled' => false,
                'cta_message' => '',
                'cta_color' => 'sun',
            ],
            'queue' => ['count' => 0, 'players' => []],
        ]);

        $this->assertFramedWheel((string) $desktopLiveView);
    }

    private function assertFramedWheel(string $html): void
    {
        $this->assertStringContainsString(asset('img/ring_frame.png'), $html);
        $this->assertStringContainsString('absolute inset-0 z-0 overflow-hidden rounded-full', $html);
        $this->assertStringContainsString('top-[calc(7%_-_', $html);
        $this->assertStringContainsString('z-30 -translate-x-1/2', $html);
        $this->assertStringContainsString('absolute inset-0 z-10 h-full w-full', $html);
        $this->assertSame(1, substr_count($html, asset('img/ring_frame.png')));

        preg_match('/id="wheel-stage" class="([^"]+)"/', $html, $stage);
        $this->assertStringNotContainsString('bg-white', $stage[1] ?? '');
    }
}
