<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\PlayRule;
use App\Models\Player;
use App\Models\SpinSession;
use Illuminate\Support\Carbon;

/**
 * Decides whether a player may start a spin, based on verification status,
 * form completion, campaign window and the configured play-frequency rules.
 * Eligibility is always keyed on the player's email identity, never the
 * session, so clearing cookies cannot bypass frequency limits.
 */
class SpinEligibilityService
{
    /**
     * @return array{eligible: bool, reason: ?string, message: ?string, next_available_at: ?string}
     */
    public function check(Player $player, Campaign $campaign): array
    {
        if (! $player->isVerified()) {
            return $this->blocked('not_verified', 'Please verify your email before spinning.');
        }

        if (! $player->hasCompletedForm()) {
            return $this->blocked('form_incomplete', 'Please complete the registration form before spinning.');
        }

        if (! $campaign->isPlayable()) {
            return $this->blocked('campaign_closed', 'This campaign is not currently accepting spins.');
        }

        foreach ($campaign->playRules()->where('is_active', true)->get() as $rule) {
            $verdict = $this->evaluateRule($rule, $player, $campaign);

            if ($verdict !== null) {
                return $verdict;
            }
        }

        return [
            'eligible' => true,
            'reason' => null,
            'message' => null,
            'next_available_at' => null,
        ];
    }

    /**
     * Returns a blocked verdict if the rule blocks the player, otherwise null.
     *
     * @return array{eligible: bool, reason: ?string, message: ?string, next_available_at: ?string}|null
     */
    protected function evaluateRule(PlayRule $rule, Player $player, Campaign $campaign): ?array
    {
        $totalSpins = $this->countSpins($player, $campaign);
        $todaySpins = $this->countSpins($player, $campaign, todayOnly: true);
        $lastSpinAt = $this->lastSpinAt($player, $campaign);

        return match ($rule->rule_type) {
            PlayRule::TYPE_ONCE_PER_CAMPAIGN => $totalSpins >= 1
                ? $this->blocked('once_per_campaign', 'You have already used your spin for this campaign.')
                : null,

            PlayRule::TYPE_ONCE_PER_DAY => $todaySpins >= 1
                ? $this->blocked(
                    'once_per_day',
                    'You have already spun today. Come back tomorrow!',
                    Carbon::tomorrow()
                )
                : null,

            PlayRule::TYPE_MAX_PER_CAMPAIGN => ($max = (int) $rule->max_spins_per_campaign) > 0 && $totalSpins >= $max
                ? $this->blocked('max_per_campaign', "You have reached the maximum of {$max} spins for this campaign.")
                : null,

            PlayRule::TYPE_MAX_PER_DAY => ($maxDay = (int) $rule->max_spins_per_day) > 0 && $todaySpins >= $maxDay
                ? $this->blocked(
                    'max_per_day',
                    "You have reached the maximum of {$maxDay} spins for today.",
                    Carbon::tomorrow()
                )
                : null,

            PlayRule::TYPE_EVERY_X_HOURS => $this->evaluateCooldown($rule, $lastSpinAt),

            default => null,
        };
    }

    /**
     * @return array{eligible: bool, reason: ?string, message: ?string, next_available_at: ?string}|null
     */
    protected function evaluateCooldown(PlayRule $rule, ?Carbon $lastSpinAt): ?array
    {
        $hours = (int) $rule->cooldown_hours;

        if ($hours <= 0 || $lastSpinAt === null) {
            return null;
        }

        $nextAt = $lastSpinAt->copy()->addHours($hours);

        if (now()->lt($nextAt)) {
            return $this->blocked(
                'cooldown',
                "You can spin again in about {$this->humanizeDiff($nextAt)}.",
                $nextAt
            );
        }

        return null;
    }

    protected function countSpins(Player $player, Campaign $campaign, bool $todayOnly = false): int
    {
        $query = SpinSession::where('player_id', $player->id)
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', [SpinSession::STATUS_ACTIVE, SpinSession::STATUS_COMPLETED]);

        if ($todayOnly) {
            $query->where('created_at', '>=', Carbon::today());
        }

        return $query->count();
    }

    protected function lastSpinAt(Player $player, Campaign $campaign): ?Carbon
    {
        $session = SpinSession::where('player_id', $player->id)
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', [SpinSession::STATUS_ACTIVE, SpinSession::STATUS_COMPLETED])
            ->latest('started_at')
            ->first();

        return $session?->started_at;
    }

    protected function humanizeDiff(Carbon $target): string
    {
        return now()->diffForHumans($target, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]);
    }

    /**
     * @return array{eligible: bool, reason: string, message: string, next_available_at: ?string}
     */
    protected function blocked(string $reason, string $message, ?Carbon $nextAvailableAt = null): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'message' => $message,
            'next_available_at' => $nextAvailableAt?->toIso8601String(),
        ];
    }
}
