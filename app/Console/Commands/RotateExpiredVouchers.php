<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\VoucherRotationService;
use Illuminate\Console\Command;

/**
 * Returns expired, unredeemed vouchers to the wheel (restock + restore odds).
 * Wired into the scheduler (hourly) in routes/console.php, and also invoked by
 * the "Rotate all expired" button on the admin Vouchers page.
 */
class RotateExpiredVouchers extends Command
{
    protected $signature = 'vouchers:rotate {--all : Rotate across every campaign instead of only the active one}';

    protected $description = 'Return expired, unredeemed vouchers back onto the wheel (restock + restore odds)';

    public function handle(VoucherRotationService $rotation): int
    {
        $all = (bool) $this->option('all');
        $campaign = $all ? null : Campaign::current();

        if (! $all && ! $campaign) {
            $this->warn('No active campaign found; nothing to rotate. Pass --all to rotate every campaign.');

            return self::SUCCESS;
        }

        $count = $rotation->rotateExpiredUnused($campaign);

        $this->info("Rotated {$count} expired voucher(s) back onto the wheel.");

        return self::SUCCESS;
    }
}
