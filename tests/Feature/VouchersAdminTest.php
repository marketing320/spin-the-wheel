<?php

namespace Tests\Feature;

use App\Livewire\Admin\Vouchers;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\User;
use App\Models\Voucher;
use App\Support\PrivacyMask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VouchersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_staff' => false]);
    }

    private function staff(): User
    {
        return User::factory()->create(['is_admin' => false, 'is_staff' => true]);
    }

    /**
     * Create an expired, unredeemed voucher for a fresh (once-sold-out) prize.
     */
    private function expiredVoucher(string $email = 'customer@example.com'): Voucher
    {
        $campaign = Campaign::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'inventory_enabled' => true,
            'inventory_quantity' => 1,
            'win_percentage' => 40,
            'weight' => 40,
        ]);
        $prize->update(['inventory_quantity' => 0]); // sell out

        $player = Player::factory()->create(['email' => $email]);
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

    /*
    |--------------------------------------------------------------------------
    | Access + privacy
    |--------------------------------------------------------------------------
    */

    public function test_admin_sees_the_vouchers_page_with_full_customer_details(): void
    {
        $this->expiredVoucher('customer@example.com');
        $this->actingAs($this->admin(), 'web');

        $this->get(route('admin.vouchers'))
            ->assertOk()
            ->assertSee('customer@example.com');
    }

    public function test_staff_sees_the_vouchers_page_with_masked_details(): void
    {
        $this->expiredVoucher('customer@example.com');
        $this->actingAs($this->staff(), 'web');

        $this->get(route('admin.vouchers'))
            ->assertOk()
            ->assertSee(PrivacyMask::reveal3('customer@example.com'))
            ->assertDontSee('customer@example.com');
    }

    public function test_plain_user_cannot_see_the_vouchers_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false, 'is_staff' => false]), 'web');

        $this->get(route('admin.vouchers'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Rotation via the component
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_rotate_an_expired_voucher_from_the_page(): void
    {
        $voucher = $this->expiredVoucher();
        $this->actingAs($this->admin(), 'web');

        Livewire::test(Vouchers::class)->call('rotate', $voucher->id);

        $voucher->refresh();
        $this->assertNotNull($voucher->rotated_at);
        $this->assertSame(1, $voucher->prize->fresh()->inventory_quantity);
    }

    public function test_admin_can_bulk_rotate_expired_vouchers(): void
    {
        $voucher = $this->expiredVoucher();
        // Bulk rotation is scoped to the active campaign; make this voucher's
        // campaign the active one.
        Campaign::query()->update(['active' => false]);
        $voucher->campaign->update(['active' => true, 'status' => Campaign::STATUS_ACTIVE]);

        $this->actingAs($this->admin(), 'web');

        Livewire::test(Vouchers::class)->call('rotateAllExpired');

        $this->assertNotNull($voucher->fresh()->rotated_at);
    }

    public function test_staff_cannot_rotate(): void
    {
        // The voucher is eligible and the admin happy-path test proves the
        // action works when allowed — so a staff user leaving it un-rotated
        // proves the admin-only guard blocked it (abort(403) is rendered, not
        // thrown, by Livewire's test harness).
        $voucher = $this->expiredVoucher();
        $this->actingAs($this->staff(), 'web');

        Livewire::test(Vouchers::class)->call('rotate', $voucher->id);

        $this->assertNull($voucher->fresh()->rotated_at);
        $this->assertSame(0, $voucher->prize->fresh()->inventory_quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | CSV export
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_export_vouchers_csv(): void
    {
        $this->expiredVoucher();
        $this->actingAs($this->admin(), 'web');

        $this->get(route('admin.vouchers.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_staff_cannot_export_vouchers_csv(): void
    {
        $this->actingAs($this->staff(), 'web');

        $this->get(route('admin.vouchers.export'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Scheduled / artisan command
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Redeemed-wins-over-expired safeguard
    |--------------------------------------------------------------------------
    */

    private function ids($paginator): \Illuminate\Support\Collection
    {
        return collect($paginator->items())->pluck('id');
    }

    public function test_redeemed_then_expired_voucher_is_classified_as_redeemed(): void
    {
        // Redeemed while valid, now past its expiry date.
        $voucher = $this->expiredVoucher();
        $voucher->update(['status' => Voucher::STATUS_REDEEMED, 'redeemed_at' => now()->subMinute()]);

        $this->assertTrue($voucher->isRedeemed());
        $this->assertFalse($voucher->isExpired());
        $this->assertSame('redeemed', $voucher->displayStatus());

        $this->actingAs($this->admin(), 'web');
        $component = Livewire::test(Vouchers::class);

        $counts = $component->viewData('counts');
        $this->assertSame(1, $counts['redeemed']);
        $this->assertSame(0, $counts['expired']);

        // Absent from the Expired tab, present in the Redeemed tab.
        $this->assertFalse($this->ids($component->set('filter', 'expired')->viewData('vouchers'))->contains($voucher->id));
        $this->assertTrue($this->ids($component->set('filter', 'redeemed')->viewData('vouchers'))->contains($voucher->id));
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

        $this->actingAs($this->admin(), 'web');
        $counts = Livewire::test(Vouchers::class)->viewData('counts');

        $this->assertSame(1, $counts['redeemed']);
        $this->assertSame(0, $counts['expired']);
        $this->assertSame(0, $counts['pending']);
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
