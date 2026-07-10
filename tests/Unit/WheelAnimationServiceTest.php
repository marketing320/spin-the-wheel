<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Prize;
use App\Services\WheelAnimationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WheelAnimationServiceTest extends TestCase
{
    use RefreshDatabase;

    private WheelAnimationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WheelAnimationService;
    }

    public function test_a_prize_with_segment_count_one_produces_a_single_segment(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['segment_count' => 1]);

        $segments = $this->service->segments($campaign, collect([$prize]));

        $this->assertCount(1, $segments);
        $this->assertSame($prize->id, $segments[0]['prize_id']);
    }

    public function test_a_prize_with_segment_count_above_one_produces_that_many_segments(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['segment_count' => 3]);

        $segments = $this->service->segments($campaign, collect([$prize]));

        $this->assertCount(3, $segments);
        foreach ($segments as $segment) {
            $this->assertSame($prize->id, $segment['prize_id']);
        }
    }

    public function test_multi_segment_slots_are_spread_around_the_wheel_not_clustered(): void
    {
        $campaign = Campaign::factory()->create();
        $a = Prize::factory()->for($campaign)->create(['name' => 'A', 'segment_count' => 3]);
        $b = Prize::factory()->for($campaign)->create(['name' => 'B', 'segment_count' => 1]);
        $c = Prize::factory()->for($campaign)->create(['name' => 'C', 'segment_count' => 2]);

        $segments = $this->service->segments($campaign, collect([$a, $b, $c]));

        // Round-robin: A, B, C, A, C, A — A's copies land at 0, 3, 5, never adjacent.
        $this->assertCount(6, $segments);
        $prizeIds = array_column($segments, 'prize_id');
        $this->assertSame([$a->id, $b->id, $c->id, $a->id, $c->id, $a->id], $prizeIds);
    }

    public function test_index_of_prize_picks_one_of_several_matching_slots(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['segment_count' => 4]);
        $segments = $this->service->segments($campaign, collect([$prize]));

        $seenIndexes = [];
        for ($i = 0; $i < 30; $i++) {
            $seenIndexes[$this->service->indexOfPrize($segments, $prize->id)] = true;
        }

        // Over enough draws, more than one of the 4 valid slots should show up.
        $this->assertGreaterThan(1, count($seenIndexes));
        foreach (array_keys($seenIndexes) as $index) {
            $this->assertContainsEquals($index, range(0, 3));
        }
    }

    public function test_index_of_prize_returns_zero_for_an_unknown_prize(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create();
        $segments = $this->service->segments($campaign, collect([$prize]));

        $this->assertSame(0, $this->service->indexOfPrize($segments, 999_999));
    }
}
