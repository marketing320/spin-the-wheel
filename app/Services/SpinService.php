<?php

namespace App\Services;

use App\Events\SpinCompleted;
use App\Events\SpinStarted;
use App\Exceptions\SpinException;
use App\Jobs\CompleteSpinJob;
use App\Jobs\ReleaseSpinJob;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\Prize;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Support\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the full spin lifecycle: eligibility → geofence → global lock →
 * server-side prize selection → animation payload → realtime broadcast, then
 * completion. All authoritative decisions happen here, never on the frontend.
 */
class SpinService
{
    public function __construct(
        protected SpinEligibilityService $eligibility,
        protected GeofenceService $geofence,
        protected SpinLockService $lock,
        protected PrizeSelectionService $prizes,
        protected WheelAnimationService $animation,
    ) {}

    /**
     * Begin a spin for the player. Returns the active SpinSession.
     *
     * @param  array<string, mixed>  $context  ['ip' => ?, 'user_agent' => ?, 'lat' => ?, 'lng' => ?]
     *
     * @throws SpinException
     */
    public function start(Player $player, array $context = []): SpinSession
    {
        $campaign = Campaign::current();

        if (! $campaign || ! $campaign->isPlayable()) {
            throw SpinException::notEligible('There is no active campaign right now.');
        }

        // Idempotency: if this player already holds the active spin, return it.
        $existing = $this->lock->currentActive();
        if ($existing && $existing->player_id === $player->id && ! $existing->completed_at) {
            return $existing;
        }

        // 1. Eligibility (verification, form, play frequency).
        $eligibility = $this->eligibility->check($player, $campaign);
        if (! $eligibility['eligible']) {
            throw SpinException::notEligible($eligibility['message'], $eligibility['next_available_at']);
        }

        // 2. Geofence (server-side Haversine).
        $geo = $this->geofence->check(
            $campaign,
            isset($context['lat']) ? (float) $context['lat'] : null,
            isset($context['lng']) ? (float) $context['lng'] : null,
            $player
        );
        if (! $geo['passed']) {
            throw SpinException::geofence($geo['message'] ?? 'You are outside the allowed location.');
        }

        // 3. Acquire the global lock + select prize atomically.
        $session = $this->acquireAndDecide($campaign, $player, $context);

        // 4. Broadcast to player + live-view.
        broadcast(new SpinStarted($this->buildStartedPayload($session)));

        // 5. Failsafe completion even if the player disconnects.
        CompleteSpinJob::dispatch($session->id)
            ->delay(now()->addMilliseconds($session->spin_duration_ms + 500));
        ReleaseSpinJob::dispatch($session->id)
            ->delay($session->buffer_ends_at->copy()->addMilliseconds(500));

        return $session;
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws SpinException
     */
    protected function acquireAndDecide(Campaign $campaign, Player $player, array $context): SpinSession
    {
        // Sweep any stuck lock before contending for it.
        $this->lock->expireStale();

        try {
            return DB::transaction(function () use ($campaign, $player, $context) {
                // Lock the candidate prize rows so stock cannot be oversold.
                $lockedPrizes = $campaign->prizes()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->lockForUpdate()
                    ->get();

                $timeout = $this->lock->lockTimeoutSeconds();
                $startedAt = now();

                // Creating with active_guard = 1 acquires the global lock. If
                // another active spin exists, the unique index rejects this and
                // the transaction rolls back.
                $session = new SpinSession([
                    'campaign_id' => $campaign->id,
                    'player_id' => $player->id,
                    'status' => SpinSession::STATUS_ACTIVE,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addSeconds($timeout),
                    'request_ip' => $context['ip'] ?? null,
                    'user_agent' => $context['user_agent'] ?? null,
                    'active_guard' => SpinSession::GUARD_ON,
                ]);
                $session->save();

                // Server-authoritative prize selection + inventory reservation.
                $prize = $this->prizes->selectAndReserve($campaign, $lockedPrizes);

                $segments = $this->animation->segments($campaign, $lockedPrizes->values());
                $targetIndex = $this->animation->indexOfPrize($segments, $prize->id);
                $duration = $this->animation->durationMs($campaign);
                $bufferDuration = (int) config('spin.spin.buffer_duration_ms', 7000);
                $finalAngle = $this->animation->finalAngleFor($targetIndex, count($segments));

                $session->forceFill([
                    'prize_id' => $prize->id,
                    'final_angle' => $finalAngle,
                    'spin_duration_ms' => $duration,
                    'animation_seed' => $this->animation->newSeed(),
                    'ends_at' => $startedAt->copy()->addMilliseconds($duration),
                    'buffer_ends_at' => $startedAt->copy()->addMilliseconds($duration + $bufferDuration),
                    'metadata' => [
                        'segments' => $segments,
                        'target_index' => $targetIndex,
                    ],
                ])->save();

                $player->forceFill(['last_spin_at' => $startedAt])->save();

                return $session->fresh(['prize', 'player', 'campaign']);
            });
        } catch (QueryException $e) {
            // Duplicate active_guard → someone else is spinning.
            if ($this->isUniqueViolation($e)) {
                throw SpinException::locked('Another player is spinning right now. Please wait a moment.');
            }

            throw $e;
        }
    }

    /**
     * Complete a spin (idempotent). Records the result and broadcasts.
     */
    public function complete(SpinSession $session): SpinSession
    {
        if ($session->completed_at || $session->status === SpinSession::STATUS_COMPLETED) {
            return $session;
        }

        // Only an active spin can be completed; expired/failed stay terminal.
        if ($session->status !== SpinSession::STATUS_ACTIVE) {
            return $session;
        }

        DB::transaction(function () use ($session) {
            $session->forceFill([
                'completed_at' => now(),
            ])->save();

            if ($session->prize_id) {
                SpinResult::firstOrCreate(
                    ['spin_session_id' => $session->id],
                    [
                        'campaign_id' => $session->campaign_id,
                        'player_id' => $session->player_id,
                        'prize_id' => $session->prize_id,
                        'result_payload' => $this->buildCompletedPayload($session),
                    ]
                );
            }
        });

        broadcast(new SpinCompleted($this->buildCompletedPayload($session->fresh(['prize']))));

        return $session;
    }

    /**
     * Public URL of the admin-configured celebration confetti image, or null
     * when the feature is disabled / no image uploaded.
     */
    protected function celebrationImageUrl(): ?string
    {
        if (! Settings::get('celebration.image_enabled')) {
            return null;
        }

        $path = Settings::get('celebration.image_path');

        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStartedPayload(SpinSession $session): array
    {
        $prize = $session->prize;
        $segments = data_get($session->metadata, 'segments', []);

        return [
            'spin_session_id' => $session->id,
            'campaign_id' => $session->campaign_id,
            'player_display' => $this->playerDisplay($session->player),
            'prize_id' => $prize?->id,
            'prize_name' => $prize?->name,
            'prize_rarity' => $prize?->rarity,
            'prize_image' => $prize?->imageUrl(),
            'confetti_level' => $prize?->confetti_level,
            'confetti_image' => $this->celebrationImageUrl(),
            'confetti_image_count' => (int) Settings::get('celebration.image_count', 30),
            'confetti_image_size' => (int) Settings::get('celebration.image_size', 44),
            'redemption_message' => $prize?->redemption_message,
            'wheel_segments' => $segments,
            'final_angle' => (float) $session->final_angle,
            'spin_duration_ms' => (int) $session->spin_duration_ms,
            'sound_duration_ms' => (int) config('spin.spin.sound_duration_ms', 11000),
            'started_at_server' => $session->started_at?->toIso8601String(),
            'ends_at_server' => $session->ends_at?->toIso8601String(),
            'buffer_ends_at_server' => $session->buffer_ends_at?->toIso8601String(),
            'phase' => $session->completed_at ? 'buffer' : 'spinning',
            'animation_seed' => $session->animation_seed,
            'server_time' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCompletedPayload(SpinSession $session): array
    {
        $prize = $session->prize;

        return [
            'spin_session_id' => $session->id,
            'prize_id' => $prize?->id,
            'prize_name' => $prize?->name,
            'prize_rarity' => $prize?->rarity,
            'prize_image' => $prize?->imageUrl(),
            'confetti_level' => $prize?->confetti_level,
            'confetti_image' => $this->celebrationImageUrl(),
            'confetti_image_count' => (int) Settings::get('celebration.image_count', 30),
            'confetti_image_size' => (int) Settings::get('celebration.image_size', 44),
            'redemption_message' => $prize?->redemption_message,
            'completed_at_server' => optional($session->completed_at ?? now())->toIso8601String(),
        ];
    }

    /**
     * Public-safe player label, honouring the live-view privacy settings.
     */
    public function playerDisplay(?Player $player): string
    {
        if (! $player) {
            return 'A player';
        }

        $showName = (bool) Settings::get('live_view.show_player_name', true);
        $showMasked = (bool) Settings::get('live_view.show_masked_email', true);

        if ($showName && $player->display_name) {
            return $player->display_name;
        }

        if ($showMasked) {
            return $player->maskedEmail();
        }

        return 'A player';
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        // MySQL error code 1062 = duplicate entry; SQLSTATE 23000.
        return in_array((string) ($e->errorInfo[1] ?? ''), ['1062'], true)
            || (string) $e->getCode() === '23000';
    }
}
