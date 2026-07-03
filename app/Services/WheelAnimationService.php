<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Prize;
use App\Support\Settings;
use Illuminate\Support\Collection;

/**
 * Produces the deterministic animation parameters that BOTH the player phone
 * and the /live-view screen replay identically.
 *
 * Wheel / pointer convention (must match resources/js/spin/spin-controller.js):
 *   - Segments are laid out clockwise starting at the top (12 o'clock).
 *   - Segment i spans local angles [i*seg, (i+1)*seg), seg = 360 / N.
 *   - The pointer is fixed at the top.
 *   - After rotating the wheel clockwise by `final_angle` degrees, the segment
 *     under the pointer is: floor(((360 - (final_angle mod 360)) mod 360) / seg).
 */
class WheelAnimationService
{
    /**
     * Ordered wheel segments for a campaign.
     *
     * @param  Collection<int, Prize>|null  $prizes
     * @return array<int, array<string, mixed>>
     */
    public function segments(Campaign $campaign, ?Collection $prizes = null): array
    {
        $prizes ??= $campaign->activePrizes()->get();

        return $prizes->values()->map(fn (Prize $prize, int $index) => [
            'index' => $index,
            'prize_id' => $prize->id,
            'label' => $prize->name,
            'color' => $prize->displayColor(),
            'rarity' => $prize->rarity,
            'image' => $prize->imageUrl(),
        ])->all();
    }

    /**
     * Compute the absolute clockwise rotation (degrees) that lands the pointer
     * on the target segment, with a little in-segment jitter for realism.
     */
    public function finalAngleFor(int $targetIndex, int $segmentCount): float
    {
        if ($segmentCount <= 0) {
            return 0.0;
        }

        $seg = 360.0 / $segmentCount;
        $baseRotations = (int) config('spin.spin.base_rotations', 6);

        $center = ($targetIndex * $seg) + ($seg / 2);
        $centerRotation = fmod(360.0 - fmod($center, 360.0) + 360.0, 360.0);

        // Jitter kept well inside the segment so the winner never changes.
        $jitterMax = $seg * 0.3;
        $jitter = ($jitterMax > 0)
            ? (random_int(-1000, 1000) / 1000) * $jitterMax
            : 0.0;

        return round(($baseRotations * 360) + $centerRotation + $jitter, 4);
    }

    public function durationMs(Campaign $campaign): int
    {
        // Authoritative spin length (ms), shared by the animation and the
        // soundtrack. Sourced from the admin-configurable default.
        return (int) Settings::get('spin.default_duration_ms', config('spin.spin.default_duration_ms', 8000));
    }

    public function newSeed(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Locate a prize's segment index within an ordered segment list.
     *
     * @param  array<int, array<string, mixed>>  $segments
     */
    public function indexOfPrize(array $segments, int $prizeId): int
    {
        foreach ($segments as $segment) {
            if ((int) $segment['prize_id'] === $prizeId) {
                return (int) $segment['index'];
            }
        }

        return 0;
    }
}
