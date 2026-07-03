<?php

namespace App\Services;

use App\Exceptions\SpinException;
use App\Models\Campaign;
use App\Models\Prize;
use Illuminate\Support\Collection;

/**
 * Server-authoritative prize selection. Supports two campaign modes:
 *
 *  - "strict"   : each prize's win_percentage drives its odds (should total 100).
 *  - "weighted" : each prize's integer weight drives its odds.
 *
 * Selection uses cryptographically secure randomness and, when inventory
 * tracking is on, decrements stock atomically. This MUST be called inside a
 * database transaction that has already locked the candidate prize rows so the
 * chosen prize cannot be oversold under concurrency.
 */
class PrizeSelectionService
{
    /**
     * Choose a winnable prize for the campaign and reserve one unit of stock.
     *
     * @param  Collection<int, Prize>|null  $lockedPrizes  Pre-locked rows (FOR UPDATE); loaded fresh if null.
     */
    public function selectAndReserve(Campaign $campaign, ?Collection $lockedPrizes = null): Prize
    {
        $prizes = ($lockedPrizes ?? $this->winnablePrizes($campaign))
            ->filter(fn (Prize $p) => $p->isWinnable())
            ->values();

        if ($prizes->isEmpty()) {
            throw SpinException::noPrizes('No prizes are currently available. Please try again later.');
        }

        $prize = $this->pick($campaign, $prizes);

        // Reserve one unit of stock atomically when inventory is tracked.
        if ($prize->inventory_enabled && $prize->inventory_quantity !== null) {
            $prize->decrement('inventory_quantity');
            $prize->refresh();
        }

        return $prize;
    }

    /**
     * Weighted random pick across the candidate prizes.
     *
     * @param  Collection<int, Prize>  $prizes
     */
    public function pick(Campaign $campaign, Collection $prizes): Prize
    {
        $weighted = $prizes
            ->map(fn (Prize $p) => ['prize' => $p, 'weight' => $this->weightFor($campaign, $p)])
            ->filter(fn ($row) => $row['weight'] > 0)
            ->values();

        // If every candidate has zero configured weight, fall back to uniform.
        if ($weighted->isEmpty()) {
            return $prizes->random();
        }

        $total = (int) $weighted->sum('weight');
        $roll = random_int(1, $total);

        $cursor = 0;
        foreach ($weighted as $row) {
            $cursor += $row['weight'];
            if ($roll <= $cursor) {
                return $row['prize'];
            }
        }

        // Numerical safety net — should be unreachable.
        return $weighted->last()['prize'];
    }

    /**
     * Integer weight for a prize under the campaign's mode. Percentages are
     * scaled by 10,000 so up to 4 decimal places are honoured.
     */
    protected function weightFor(Campaign $campaign, Prize $prize): int
    {
        if ($campaign->prize_mode === Campaign::MODE_STRICT) {
            return (int) round(((float) $prize->win_percentage) * 10_000);
        }

        // Weighted mode: default to weight 1 so a prize with no explicit weight
        // still has a chance rather than being silently excluded.
        return max(0, (int) ($prize->weight ?? 1));
    }

    /**
     * @return Collection<int, Prize>
     */
    public function winnablePrizes(Campaign $campaign): Collection
    {
        return $campaign->prizes()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Validate a campaign's prize configuration for the admin UI.
     *
     * @return array{valid: bool, warnings: array<int, string>, total_percentage: float, total_weight: int}
     */
    public function validateConfiguration(Campaign $campaign): array
    {
        $prizes = $campaign->activePrizes()->get();
        $warnings = [];

        $totalPercentage = (float) $prizes->sum(fn (Prize $p) => (float) $p->win_percentage);
        $totalWeight = (int) $prizes->sum(fn (Prize $p) => (int) ($p->weight ?? 0));

        if ($prizes->isEmpty()) {
            $warnings[] = 'This campaign has no active prizes; players will not be able to win anything.';
        }

        if ($campaign->prize_mode === Campaign::MODE_STRICT) {
            if (abs($totalPercentage - 100.0) > 0.01) {
                $warnings[] = sprintf(
                    'Active prize percentages total %.2f%%. In strict mode they should total 100%%.',
                    $totalPercentage
                );
            }
        } elseif ($totalWeight <= 0 && $prizes->isNotEmpty()) {
            $warnings[] = 'No active prize has a positive weight; selection will fall back to uniform odds.';
        }

        $winnable = $prizes->filter(fn (Prize $p) => $p->isWinnable());
        if ($prizes->isNotEmpty() && $winnable->isEmpty()) {
            $warnings[] = 'All active prizes are out of stock; no prize can currently be won.';
        }

        return [
            'valid' => empty($warnings),
            'warnings' => $warnings,
            'total_percentage' => round($totalPercentage, 4),
            'total_weight' => $totalWeight,
        ];
    }
}
