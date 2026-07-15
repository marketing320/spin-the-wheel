<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\EmailOtp;
use App\Models\GeofenceLog;
use App\Models\Player;
use App\Models\PlayerFormResponse;
use App\Models\Prize;
use App\Models\SpinQueueEntry;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Wipes all player-side test data (players, spins, wins, vouchers, OTPs, form
 * responses, geofence + queue logs) so a production instance that was used for
 * testing can be reset to a clean slate before going live.
 *
 * Players are NOT campaign-scoped, so the wipe is global by design — "refresh
 * the players and winners data" means every registered player and their entire
 * play history, not one campaign's slice. Prize inventory for the active
 * campaign is restored to its pre-spin level by adding back the units that were
 * reserved during the spins being wiped.
 */
class CampaignResetService
{
    /**
     * Delete all player-side data and restock the active campaign's prizes.
     *
     * @return array<string,int> Row counts affected, keyed by entity.
     */
    public function reset(?Campaign $campaign = null): array
    {
        return DB::transaction(function () use ($campaign) {
            // Capture how many units of each prize were reserved BEFORE we
            // delete the sessions — each spin session with a prize decremented
            // that prize's stock by one (see PrizeSelectionService).
            $reservedPerPrize = SpinSession::query()
                ->whereNotNull('prize_id')
                ->selectRaw('prize_id, COUNT(*) as reserved')
                ->groupBy('prize_id')
                ->pluck('reserved', 'prize_id');

            // Delete children before parents so the wipe works regardless of
            // FK cascade rules.
            $stats = [
                'vouchers' => Voucher::query()->delete(),
                'spin_results' => SpinResult::query()->delete(),
                'spin_sessions' => SpinSession::query()->delete(),
                'queue_entries' => SpinQueueEntry::query()->delete(),
                'geofence_logs' => GeofenceLog::query()->delete(),
                'form_responses' => PlayerFormResponse::query()->delete(),
                'players' => Player::query()->delete(),
                'otps' => EmailOtp::query()->delete(),
            ];

            $stats['prizes_restocked'] = $this->restoreInventory($campaign, $reservedPerPrize);

            return $stats;
        });
    }

    /**
     * Add the reserved units back to the active campaign's tracked prizes. Uses
     * a raw increment so Prize's out-of-stock `saving` hook does not fire — we
     * are only restoring stock, never re-deriving odds. Note: a prize that sold
     * out during testing had its odds zeroed by the hook; restocking does not
     * un-zero them, so the admin should re-check odds for any sold-out prize.
     *
     * @param  Collection<int,int>  $reservedPerPrize
     */
    private function restoreInventory(?Campaign $campaign, Collection $reservedPerPrize): int
    {
        if (! $campaign) {
            return 0;
        }

        $restocked = 0;

        $prizes = Prize::query()
            ->where('campaign_id', $campaign->id)
            ->where('inventory_enabled', true)
            ->whereNotNull('inventory_quantity')
            ->get(['id']);

        foreach ($prizes as $prize) {
            $qty = (int) ($reservedPerPrize[$prize->id] ?? 0);

            if ($qty > 0) {
                Prize::query()->whereKey($prize->id)->increment('inventory_quantity', $qty);
                $restocked++;
            }
        }

        return $restocked;
    }
}
