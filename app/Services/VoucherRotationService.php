<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Prize;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * Returns expired, unredeemed vouchers back onto the wheel. When a player wins
 * a voucher-type prize its stock is reserved immediately, so a voucher that
 * expires without being redeemed has locked up a unit that no one will ever
 * claim. Rotating it gives that unit back: the prize's inventory is
 * incremented and its configured odds are restored from the base_* snapshot
 * (which survives the sell-out zeroing), so the prize can be won again.
 */
class VoucherRotationService
{
    /**
     * Rotate a single voucher. No-op (returns false) unless the voucher is an
     * expired, never-redeemed, not-yet-rotated one. Row-locks both the voucher
     * and its prize so a concurrent redeem/rotate can't double-count stock.
     */
    public function rotate(Voucher $voucher): bool
    {
        return DB::transaction(function () use ($voucher) {
            $locked = Voucher::whereKey($voucher->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isUnusedExpired()) {
                return false;
            }

            $prize = Prize::whereKey($locked->prize_id)->lockForUpdate()->first();

            // Only tracked inventory needs restoring; an untracked prize is
            // always available, so there is nothing to return to the pool.
            if ($prize && $prize->inventory_enabled && $prize->inventory_quantity !== null) {
                $prize->inventory_quantity += 1;

                if ($prize->base_win_percentage !== null) {
                    $prize->win_percentage = $prize->base_win_percentage;
                }

                if ($prize->base_weight !== null) {
                    $prize->weight = $prize->base_weight;
                }

                $prize->save();
            }

            $locked->forceFill([
                'status' => Voucher::STATUS_EXPIRED,
                'rotated_at' => now(),
            ])->save();

            return true;
        });
    }

    /**
     * Rotate every eligible expired/unredeemed voucher, optionally scoped to a
     * single campaign.
     *
     * @return int Number of vouchers rotated.
     */
    public function rotateExpiredUnused(?Campaign $campaign = null): int
    {
        $rotated = 0;

        Voucher::query()
            ->whereNull('rotated_at')
            ->whereNull('redeemed_at')
            ->where('status', '!=', Voucher::STATUS_REDEEMED)
            ->where('expires_at', '<', now())
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->select('id')
            ->chunkById(200, function ($vouchers) use (&$rotated) {
                foreach ($vouchers as $voucher) {
                    if ($this->rotate($voucher)) {
                        $rotated++;
                    }
                }
            });

        return $rotated;
    }
}
