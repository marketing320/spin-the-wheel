<?php

namespace Tests\Feature;

use App\Livewire\Admin\Prizes;
use App\Livewire\Admin\RedeemVoucher;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\User;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class VoucherReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_snapshots_the_prize_staff_redemption_reminder(): void
    {
        $message = 'Minimum spend RM50. Not valid with other promotions.';
        [$prize, $voucher] = $this->makeVoucher($message);

        $this->assertSame($message, $voucher->staff_redemption_reminder);

        $prize->update(['staff_redemption_reminder' => 'Updated terms']);

        $this->assertSame($message, $voucher->fresh()->staff_redemption_reminder);
    }

    public function test_admin_can_configure_a_reminder_and_pending_legacy_vouchers_are_updated(): void
    {
        $message = 'Please confirm a minimum spend of RM80.';
        [$prize, $voucher] = $this->makeVoucher();
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(Prizes::class)
            ->call('edit', $prize->id)
            ->set('staff_redemption_reminder', "  {$message}  ")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($message, $prize->fresh()->staff_redemption_reminder);
        $this->assertSame($message, $voucher->fresh()->staff_redemption_reminder);
    }

    public function test_staff_and_admin_receive_an_informational_warning_and_can_still_redeem(): void
    {
        $message = 'Minimum spend RM50 applies.';
        $users = [
            User::factory()->create(['is_admin' => true, 'is_staff' => false]),
            User::factory()->create(['is_admin' => false, 'is_staff' => true]),
        ];

        foreach ($users as $user) {
            [, $voucher] = $this->makeVoucher($message);

            Livewire::actingAs($user)
                ->test(RedeemVoucher::class)
                ->set('codeInput', $voucher->code)
                ->call('lookup')
                ->assertSet('pending.staff_redemption_reminder', $message)
                ->assertDispatched(
                    'voucher-reminder',
                    title: 'Staff redemption reminder',
                    message: $message,
                )
                ->call('confirmRedeem');

            $voucher->refresh();
            $this->assertSame(Voucher::STATUS_REDEEMED, $voucher->status);
            $this->assertSame($user->id, $voucher->redeemed_by);
        }
    }

    public function test_all_native_javascript_popup_boxes_have_been_replaced(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path()),
        );
        $sweetAlertConfirmations = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['js', 'php'], true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString('wire:confirm', $contents, $file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/(?<![\w.])(?:window\.)?(?:alert|confirm|prompt)\s*\(/',
                $contents,
                $file->getPathname(),
            );

            $sweetAlertConfirmations += preg_match_all('/\bdata-swal-confirm=/', $contents);
        }

        $this->assertSame(11, $sweetAlertConfirmations);
    }

    /** @return array{Prize, Voucher} */
    private function makeVoucher(?string $reminder = null): array
    {
        $campaign = Campaign::factory()->create();
        $player = Player::factory()->create();
        $prize = Prize::factory()->for($campaign)->create([
            'type' => Prize::TYPE_VOUCHER,
            'staff_redemption_reminder' => $reminder,
        ]);
        $session = SpinSession::create([
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
            'status' => SpinSession::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        $result = SpinResult::create([
            'spin_session_id' => $session->id,
            'campaign_id' => $campaign->id,
            'player_id' => $player->id,
            'prize_id' => $prize->id,
        ]);

        return [$prize, app(VoucherService::class)->generateForResult($result)];
    }
}
