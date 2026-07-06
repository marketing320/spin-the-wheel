<?php

namespace App\Http\Controllers;

use App\Exceptions\SpinException;
use App\Models\Campaign;
use App\Models\SpinSession;
use App\Services\GeofenceService;
use App\Services\SpinEligibilityService;
use App\Services\SpinLockService;
use App\Services\SpinQueueService;
use App\Services\SpinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JSON endpoints that drive the spin from the player's browser. All are
 * session-authenticated through the "player" guard and never trust the client
 * for the outcome — the prize and eligibility are decided server-side.
 */
class SpinActionController extends Controller
{
    public function __construct(
        protected SpinService $spins,
        protected SpinEligibilityService $eligibility,
        protected GeofenceService $geofence,
        protected SpinLockService $lock,
        protected SpinQueueService $queue,
    ) {}

    public function eligibility(): JsonResponse
    {
        $player = Auth::guard('player')->user();
        $campaign = Campaign::current();

        if (! $campaign) {
            return response()->json(['eligible' => false, 'reason' => 'no_campaign', 'message' => 'No active campaign.']);
        }

        $result = $this->eligibility->check($player, $campaign);
        $active = $this->lock->currentActive();
        $queue = $this->queue->status($campaign, $player);

        return response()->json(array_merge($result, [
            'spin_in_progress' => $active !== null && $active->player_id !== $player->id,
            'queue' => $queue,
            'can_start' => $result['eligible'] && $active === null && (! $queue['queued'] || $queue['position'] === 1),
            'geofence_enabled' => (bool) ($campaign->geofenceSetting?->enabled),
        ]));
    }

    public function geofenceCheck(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $player = Auth::guard('player')->user();
        $campaign = Campaign::current();

        if (! $campaign) {
            return response()->json(['passed' => false, 'message' => 'No active campaign.'], 422);
        }

        $result = $this->geofence->check(
            $campaign,
            isset($data['lat']) ? (float) $data['lat'] : null,
            isset($data['lng']) ? (float) $data['lng'] : null,
            $player
        );

        return response()->json($result);
    }

    public function joinQueue(): JsonResponse
    {
        $player = Auth::guard('player')->user();
        $campaign = Campaign::current();

        if (! $campaign) {
            return response()->json(['ok' => false, 'message' => 'No active campaign.'], 422);
        }

        $eligibility = $this->eligibility->check($player, $campaign);
        if (! $eligibility['eligible']) {
            return response()->json(array_merge(['ok' => false], $eligibility), 422);
        }

        $active = $this->lock->currentActive();
        if (! $active && ! $this->queue->hasWaiting($campaign)) {
            return response()->json([
                'ok' => false,
                'reason' => 'spin_available',
                'message' => 'The wheel is available. Tap again to spin.',
            ], 409);
        }

        return response()->json([
            'ok' => true,
            'queued' => true,
            'queue' => $this->queue->join($campaign, $player),
            'message' => 'You have joined the spin queue.',
        ], 202);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $player = Auth::guard('player')->user();
        $campaign = Campaign::current();

        if (! $campaign) {
            return response()->json(['ok' => false, 'reason' => 'no_campaign', 'message' => 'No active campaign.'], 422);
        }

        $eligibility = $this->eligibility->check($player, $campaign);
        if (! $eligibility['eligible']) {
            return response()->json(array_merge(['ok' => false], $eligibility), 422);
        }

        $geo = $this->geofence->check(
            $campaign,
            isset($data['lat']) ? (float) $data['lat'] : null,
            isset($data['lng']) ? (float) $data['lng'] : null,
            $player,
        );
        if (! $geo['passed']) {
            return response()->json(array_merge(['ok' => false, 'reason' => 'geofence_blocked'], $geo), 403);
        }

        $active = $this->lock->currentActive();
        $queue = $this->queue->status($campaign, $player);

        if (($active && $active->player_id !== $player->id)
            || (! $active && $this->queue->hasWaiting($campaign) && ! $this->queue->isFirst($campaign, $player))) {
            return response()->json([
                'ok' => true,
                'queued' => true,
                'queue' => $this->queue->join($campaign, $player),
                'message' => 'You have joined the spin queue.',
            ], 202);
        }

        try {
            $session = $this->spins->start($player, [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
            ]);
        } catch (SpinException $e) {
            if ($e->reason === 'spin_in_progress') {
                return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'queue' => $this->queue->join($campaign, $player),
                    'message' => 'You have joined the spin queue.',
                ], 202);
            }

            return response()->json(
                array_merge(['ok' => false], $e->toArray()),
                $this->statusForReason($e->reason)
            );
        }

        $this->queue->remove($campaign, $player);

        return response()->json([
            'ok' => true,
            'spin' => $this->spins->buildStartedPayload($session),
        ]);
    }

    public function active(): JsonResponse
    {
        $session = $this->lock->currentActive();

        if (! $session) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'spin' => $this->spins->buildStartedPayload($session),
        ]);
    }

    public function complete(SpinSession $spin): JsonResponse
    {
        $player = Auth::guard('player')->user();

        // Only the owning player may trigger completion from the browser; the
        // failsafe job handles disconnects regardless.
        if ($spin->player_id !== $player->id) {
            abort(403);
        }

        $spin = $this->spins->complete($spin);

        return response()->json([
            'ok' => true,
            'result' => $this->spins->buildCompletedPayload($spin->fresh(['prize', 'result.voucher'])),
        ]);
    }

    protected function statusForReason(string $reason): int
    {
        return match ($reason) {
            'spin_in_progress' => 423,
            'geofence_blocked' => 403,
            'no_prizes' => 409,
            default => 422,
        };
    }
}
