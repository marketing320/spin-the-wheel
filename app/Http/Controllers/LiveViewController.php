<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SpinResult;
use App\Services\SpinLockService;
use App\Services\SpinQueueService;
use App\Services\SpinService;
use App\Services\WheelAnimationService;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Public event displays. The main live view lists recent prize wins; the roadshow
 * and front-view screens mirror active player spins without exposing private data.
 */
class LiveViewController extends Controller
{
    public function __construct(
        protected WheelAnimationService $animation,
        protected SpinLockService $lock,
        protected SpinService $spins,
        protected SpinQueueService $queue,
    ) {}

    public function index(): View
    {
        $campaign = Campaign::current();

        return view('live-view', [
            'campaign' => $campaign,
            'winners' => $this->recentWinners($campaign),
            'branding' => Settings::get('live_view.branding'),
            'leaderboardRoute' => route('live-view.leaderboard'),
        ]);
    }

    /**
     * The latest completed prize wins for the current campaign, newest first.
     *
     * @return array<int, array{name: string, prize: string, rarity: string, won_at: string}>
     */
    protected function recentWinners(?Campaign $campaign): array
    {
        if (! $campaign) {
            return [];
        }

        $results = SpinResult::query()
            ->join('spin_sessions', 'spin_sessions.id', '=', 'spin_results.spin_session_id')
            ->select('spin_results.*')
            ->with(['player', 'prize', 'spinSession'])
            ->where('spin_results.campaign_id', $campaign->id)
            ->whereNotNull('spin_sessions.completed_at')
            ->orderByRaw('COALESCE(spin_sessions.completed_at, spin_results.created_at) DESC')
            ->orderByDesc('spin_results.id')
            ->limit(10)
            ->get();

        return $results->map(function (SpinResult $result): array {
            return [
                'name' => $this->spins->playerDisplay($result->player),
                'prize' => $result->prize?->name ?? 'Prize winner',
                'rarity' => $result->prize?->rarity ?? 'common',
                'won_at' => ($result->spinSession?->completed_at ?? $result->created_at)?->toIso8601String() ?? '',
            ];
        })->all();
    }

    public function leaderboardSnapshot(): JsonResponse
    {
        return response()
            ->json(['winners' => $this->recentWinners(Campaign::current())])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Portrait layout for LED roadshow panels (344px single / 688px double).
     * Uses the shared realtime spin data and animation used by /front-view.
     */
    public function roadshow(): View
    {
        return view('roadshow-live', $this->sharedData());
    }

    /**
     * Full-screen roadshow display: like /roadshow-live, except idle shows an
     * admin-uploaded image slideshow (full takeover) instead of the
     * idle-spinning wheel — see resources/js/spin/live-view.js's optional
     * #idle-slideshow/#live-content handling. With the banner disabled or no
     * images uploaded, it silently falls back to the normal idle-spin wheel.
     */
    public function frontView(): View
    {
        $data = $this->sharedData();

        $enabled = (bool) Settings::get('front_view.enabled');
        $images = $enabled ? (array) Settings::get('front_view.images', []) : [];

        $data['settings']['front_view_images'] = collect($images)
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->all();
        $data['settings']['front_view_interval_seconds'] = (int) Settings::get('front_view.interval_seconds', 6);

        return view('front-view', $data);
    }

    /**
     * @return array{campaign: ?Campaign, segments: array, settings: array, queue: array}
     */
    protected function sharedData(): array
    {
        $campaign = Campaign::current();
        $segments = $campaign ? $this->animation->segments($campaign) : [];

        return [
            'campaign' => $campaign,
            'segments' => $segments,
            'settings' => [
                'idle_message' => Settings::get('live_view.idle_message'),
                'branding' => Settings::get('live_view.branding'),
                'auto_reset_seconds' => (int) Settings::get('live_view.auto_reset_seconds', 12),
                'cta_enabled' => (bool) Settings::get('live_view.cta_enabled'),
                'cta_message' => (string) Settings::get('live_view.cta_message', ''),
                'cta_color' => (string) Settings::get('live_view.cta_color', 'sun'),
            ],
            'queue' => $this->queue->snapshot($campaign),
        ];
    }

    /**
     * Current active spin payload (or idle), for when the screen loads mid-spin.
     */
    public function active(): JsonResponse
    {
        $session = $this->lock->currentActive();
        $queue = $this->queue->snapshot(Campaign::current());

        if (! $session) {
            return response()->json(['active' => false, 'queue' => $queue]);
        }

        return response()->json([
            'active' => true,
            'spin' => $this->spins->buildStartedPayload($session),
            'queue' => $queue,
        ]);
    }
}
