<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\Voucher;
use App\Services\VoucherRotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherRotationServiceTest extends TestCase
{
    use RefreshDatabase;

    private VoucherRotationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VoucherRotationService::class);
    }

    /**
     * Build a voucher (with its full spin-result chain) for the given prize.
     */
    private function makeVoucher(Prize $prize, array $overrides = []): Voucher
    {
        $campaign = $prize->campaign;
        $player = Player::factory()->create();

        $session = SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
        ]);

        $result = SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);

        return Voucher::create(array_merge([
            'spin_result_id' => $result->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'code' => 'VC'.$result->id,
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ], $overrides));
    }

    private function soldOutPrize(Campaign $campaign): Prize
    {
        // Created in stock (captures base odds), then sold out (which zeroes
        // the live odds but preserves base_*).
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 1,
            'win_percentage' => 40,
            'weight' => 40,
        ]);

        $prize->update(['inventory_quantity' => 0]);

        return $prize;
    }

    public function test_selling_out_zeroes_live_odds_but_keeps_base(): void
    {
        $prize = $this->soldOutPrize(Campaign::factory()->create());

        $this->assertSame('0.0000', (string) $prize->win_percentage);
        $this->assertSame(0, $prize->weight);
        $this->assertSame('40.0000', (string) $prize->base_win_percentage);
        $this->assertSame(40, $prize->base_weight);
    }

    public function test_rotating_expired_voucher_restocks_and_restores_odds(): void
    {
        $prize = $this->soldOutPrize(Campaign::factory()->create());
        $voucher = $this->makeVoucher($prize);

        $this->assertTrue($this->service->rotate($voucher));

        $prize->refresh();
        $this->assertSame(1, $prize->inventory_quantity);
        $this->assertSame('40.0000', (string) $prize->win_percentage);
        $this->assertSame(40, $prize->weight);

        $voucher->refresh();
        $this->assertNotNull($voucher->rotated_at);
        $this->assertSame(Voucher::STATUS_EXPIRED, $voucher->status);
    }

    public function test_rotation_is_idempotent(): void
    {
        $prize = $this->soldOutPrize(Campaign::factory()->create());
        $voucher = $this->makeVoucher($prize);

        $this->assertTrue($this->service->rotate($voucher));
        $this->assertFalse($this->service->rotate($voucher->fresh()));

        // Stock restored exactly once.
        $this->assertSame(1, $prize->fresh()->inventory_quantity);
    }

    public function test_redeemed_voucher_is_never_rotated(): void
    {
        $prize = $this->soldOutPrize(Campaign::factory()->create());
        $voucher = $this->makeVoucher($prize, [
            'status' => Voucher::STATUS_REDEEMED,
            'redeemed_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($this->service->rotate($voucher));
        $this->assertSame(0, $prize->fresh()->inventory_quantity);
    }

    public function test_unexpired_voucher_is_not_rotated(): void
    {
        $prize = $this->soldOutPrize(Campaign::factory()->create());
        $voucher = $this->makeVoucher($prize, ['expires_at' => now()->addDay()]);

        $this->assertFalse($this->service->rotate($voucher));
        $this->assertSame(0, $prize->fresh()->inventory_quantity);
    }

    public function test_untracked_prize_rotates_voucher_without_touching_stock(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => false,
            'inventory_quantity' => null,
            'win_percentage' => 25,
        ]);
        $voucher = $this->makeVoucher($prize);

        $this->assertTrue($this->service->rotate($voucher));

        $this->assertNull($prize->fresh()->inventory_quantity);
        $this->assertSame('25.0000', (string) $prize->fresh()->win_percentage);
        $this->assertNotNull($voucher->fresh()->rotated_at);
    }

    public function test_bulk_rotation_counts_only_eligible_vouchers(): void
    {
        $campaign = Campaign::factory()->create();
        $prize = $this->soldOutPrize($campaign);

        $this->makeVoucher($prize); // eligible
        $this->makeVoucher($prize); // eligible
        $this->makeVoucher($prize, ['expires_at' => now()->addDay()]); // not expired
        $this->makeVoucher($prize, ['status' => Voucher::STATUS_REDEEMED, 'redeemed_at' => now()]); // redeemed

        $rotated = $this->service->rotateExpiredUnused($campaign);

        $this->assertSame(2, $rotated);
        // Two units returned to the once-sold-out prize.
        $this->assertSame(2, $prize->fresh()->inventory_quantity);
    }
}
