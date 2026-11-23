<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an expired, unredeemed voucher for a fresh (once-sold-out) prize.
     */
    private function expiredVoucher(): Voucher
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 1,
            'win_percentage' => 40,
            'weight' => 40,
        ]);
        $prize->update(['inventory_quantity' => 0]); // sell out

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

        return Voucher::create([
            'spin_result_id' => $result->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'code' => 'ABCDE12345',
            'status' => Voucher::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ]);
    }

    public function test_rotate_command_processes_expired_vouchers(): void
    {
        $voucher = $this->expiredVoucher();
        Campaign::query()->update(['active' => false]);
        $voucher->campaign->update(['active' => true, 'status' => Campaign::STATUS_ACTIVE]);

        $this->artisan('vouchers:rotate')->assertSuccessful();

        $voucher->refresh();
        $this->assertNotNull($voucher->rotated_at);
        $this->assertSame(1, $voucher->prize->fresh()->inventory_quantity);
    }

    public function test_redeemed_then_expired_voucher_is_classified_as_redeemed(): void
    {
        // Redeemed while valid, now past its expiry date.
        $voucher = $this->expiredVoucher();
        $voucher->update(['status' => Voucher::STATUS_REDEEMED, 'redeemed_at' => now()->subMinute()]);

        $this->assertTrue($voucher->isRedeemed());
        $this->assertFalse($voucher->isExpired());
        $this->assertSame('redeemed', $voucher->displayStatus());

        $this->assertTrue(Voucher::redeemed()->whereKey($voucher->id)->exists());
        $this->assertFalse(Voucher::expiredUnused()->whereKey($voucher->id)->exists());
    }

    public function test_voucher_with_redeemed_at_but_stale_status_still_counts_as_redeemed(): void
    {
        // The safeguard's whole point: redeemed_at is set but the status column
        // was never flipped. redeemed_at must win everywhere.
        $voucher = $this->expiredVoucher();
        $voucher->forceFill(['redeemed_at' => now()->subMinute()])->save(); // status stays 'pending'

        $this->assertTrue($voucher->isRedeemed());
        $this->assertFalse($voucher->isExpired());
        $this->assertFalse($voucher->isRedeemable());
        $this->assertSame('redeemed', $voucher->displayStatus());

        $this->assertTrue(Voucher::redeemed()->whereKey($voucher->id)->exists());
        $this->assertFalse(Voucher::expiredUnused()->whereKey($voucher->id)->exists());
        $this->assertFalse(Voucher::stillActive()->whereKey($voucher->id)->exists());
    }

    public function test_rotation_skips_a_redeemed_voucher_even_with_stale_status(): void
    {
        $voucher = $this->expiredVoucher();
        $voucher->forceFill(['redeemed_at' => now()->subMinute()])->save(); // status stays 'pending'

        $rotated = app(\App\Services\VoucherRotationService::class)->rotate($voucher);

        $this->assertFalse($rotated);
        $this->assertNull($voucher->fresh()->rotated_at);
        $this->assertSame(0, $voucher->prize->fresh()->inventory_quantity);
    }
}
