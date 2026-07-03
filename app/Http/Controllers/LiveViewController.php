<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\SpinLockService;
use App\Services\SpinService;
use App\Services\WheelAnimationService;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * The public event-screen display. Mirrors the active player spin in realtime
 * and shows an idle screen when nobody is spinning. Exposes no sensitive data.
 */
class LiveViewController extends Controller
{
    public function __construct(
        protected WheelAnimationService $animation,
        protected SpinLockService $lock,
        protected SpinService $spins,
    ) {}

    public function index(): View
    {
        $campaign = Campaign::current();
        $segments = $campaign ? $this->animation->segments($campaign) : [];

        return view('live-view', [
            'campaign' => $campaign,
            'segments' => $segments,
            'settings' => [
                'idle_message' => Settings::get('live_view.idle_message'),
                'branding' => Settings::get('live_view.branding'),
                'auto_reset_seconds' => (int) Settings::get('live_view.auto_reset_seconds', 12),
            ],
        ]);
    }

    /**
     * Current active spin payload (or idle), for when the screen loads mid-spin.
     */
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
}
