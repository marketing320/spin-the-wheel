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

    public function test_depleting_the_last_unit_zeroes_odds_in_the_database(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 1,
            'win_percentage' => 40,
            'weight' => 40,
        ]);

        $this->service->selectAndReserve($campaign);

        $fresh = $prize->fresh();
        $this->assertSame(0, $fresh->inventory_quantity);
        $this->assertSame('0.0000', (string) $fresh->win_percentage);
        $this->assertSame(0, $fresh->weight);
    }

    public function test_reserving_stock_above_zero_leaves_odds_untouched(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 3,
            'win_percentage' => 40,
            'weight' => 40,
        ]);

        $this->service->selectAndReserve($campaign);

        $fresh = $prize->fresh();
        $this->assertSame(2, $fresh->inventory_quantity);
        $this->assertSame('40.0000', (string) $fresh->win_percentage);
        $this->assertSame(40, $fresh->weight);
    }

    public function test_untracked_inventory_is_never_zeroed_even_at_zero_quantity(): void
    {
        // inventory_enabled = false: a stale 0 in the quantity column must not
        // be treated as "out of stock".
        $prize = Prize::factory()->create([
            'inventory_enabled' => false,
            'inventory_quantity' => 0,
            'win_percentage' => 40,
            'weight' => 40,
        ]);

        $this->assertSame('40.0000', (string) $prize->win_percentage);
        $this->assertSame(40, $prize->weight);
        $this->assertFalse($prize->isOutOfStock());
        $this->assertTrue($prize->isWinnable());
    }

    public function test_saving_an_out_of_stock_prize_via_the_model_zeroes_odds(): void
    {
        $prize = Prize::factory()->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 5,
            'win_percentage' => 40,
            'weight' => 40,
        ]);

        $prize->update(['inventory_quantity' => 0]);

        $fresh = $prize->fresh();
        $this->assertSame('0.0000', (string) $fresh->win_percentage);
        $this->assertSame(0, $fresh->weight);
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
