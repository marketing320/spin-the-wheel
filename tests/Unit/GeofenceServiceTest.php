<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\GeofenceSetting;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeofenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeofenceService::class);
    }

    public function test_haversine_distance_is_correct(): void
    {
        // ~0.001 degrees of longitude at the equator is ~111.32 m.
        $distance = GeofenceService::haversine(0, 0, 0, 0.001);
        $this->assertEqualsWithDelta(111.32, $distance, 1.0);
    }

    public function test_it_passes_when_geofence_disabled(): void
    {
        $campaign = Campaign::factory()->create();

        $result = $this->service->check($campaign, null, null);

        $this->assertTrue($result['passed']);
        $this->assertFalse($result['enabled']);
    }

    public function test_it_blocks_when_outside_radius_and_logs(): void
    {
        $campaign = Campaign::factory()->create();
        GeofenceSetting::create([
            'campaign_id' => $campaign->id,
            'enabled' => true,
            'latitude' => 3.0,
            'longitude' => 101.0,
            'radius_meters' => 100,
        ]);

        // A point far away.
        $result = $this->service->check($campaign, 3.5, 101.5);

        $this->assertFalse($result['passed']);
        $this->assertSame('outside_radius', $result['reason']);
        $this->assertDatabaseHas('geofence_logs', ['campaign_id' => $campaign->id, 'passed' => false]);
    }

    public function test_it_passes_when_inside_radius(): void
    {
        $campaign = Campaign::factory()->create();
        GeofenceSetting::create([
            'campaign_id' => $campaign->id,
            'enabled' => true,
            'latitude' => 3.0,
            'longitude' => 101.0,
            'radius_meters' => 500,
        ]);

        $result = $this->service->check($campaign, 3.0005, 101.0005);

        $this->assertTrue($result['passed']);
        $this->assertSame('inside_radius', $result['reason']);
    }

    public function test_missing_location_is_blocked_when_enabled(): void
    {
        $campaign = Campaign::factory()->create();
        GeofenceSetting::create([
            'campaign_id' => $campaign->id,
            'enabled' => true,
            'latitude' => 3.0,
            'longitude' => 101.0,
            'radius_meters' => 100,
        ]);

        $result = $this->service->check($campaign, null, null);

        $this->assertFalse($result['passed']);
        $this->assertSame('location_unavailable', $result['reason']);
    }
}
