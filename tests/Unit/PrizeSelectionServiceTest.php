<?php

namespace Tests\Unit;

use App\Exceptions\SpinException;
use App\Models\Campaign;
use App\Models\Prize;
use App\Services\PrizeSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeSelectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrizeSelectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PrizeSelectionService::class);
    }

    public function test_it_selects_an_active_prize_server_side(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create(['name' => 'Only Prize']);

        $selected = $this->service->selectAndReserve($campaign);

        $this->assertSame($prize->id, $selected->id);
    }

    public function test_out_of_stock_prizes_are_never_selected(): void
    {
        $campaign = Campaign::factory()->create();
        $inStock = Prize::factory()->for($campaign)->create(['name' => 'In stock', 'weight' => 5]);
        Prize::factory()->for($campaign)->outOfStock()->create(['name' => 'Sold out', 'weight' => 1000]);

        for ($i = 0; $i < 25; $i++) {
            $this->assertSame($inStock->id, $this->service->selectAndReserve($campaign)->id);
        }
    }

    public function test_inactive_prizes_are_excluded(): void
    {
        $campaign = Campaign::factory()->create();
        $active = Prize::factory()->for($campaign)->create(['weight' => 1]);
        Prize::factory()->for($campaign)->inactive()->create(['weight' => 1000]);

        $this->assertSame($active->id, $this->service->selectAndReserve($campaign)->id);
    }

    public function test_it_throws_when_no_prize_is_winnable(): void
    {
        $campaign = Campaign::factory()->create();
        Prize::factory()->for($campaign)->outOfStock()->create();

        $this->expectException(SpinException::class);
        $this->service->selectAndReserve($campaign);
    }

    public function test_it_decrements_inventory_on_reserve(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 3,
        ]);

        $this->service->selectAndReserve($campaign);

        $this->assertSame(2, $prize->fresh()->inventory_quantity);
    }

    public function test_strict_mode_respects_percentages(): void
    {
        $campaign = Campaign::factory()->strict()->create();
        $winner = Prize::factory()->for($campaign)->create(['win_percentage' => 100, 'weight' => null]);
        Prize::factory()->for($campaign)->create(['win_percentage' => 0, 'weight' => null]);

        for ($i = 0; $i < 15; $i++) {
            $this->assertSame($winner->id, $this->service->pick($campaign, $campaign->activePrizes()->get())->id);
        }
    }
}
