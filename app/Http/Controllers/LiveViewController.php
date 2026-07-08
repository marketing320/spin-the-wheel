<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\SpinLockService;
use App\Services\SpinQueueService;
use App\Services\SpinService;
use App\Services\WheelAnimationService;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
        protected SpinQueueService $queue,
    ) {}

    public function index(): View
    {
        return view('live-view', $this->sharedData());
    }

    /**
     * Portrait layout for LED roadshow panels (344px single / 688px double).
     * Same data, same realtime JS (resources/js/spin/live-view.js) and the
     * same DOM element IDs as /live-view — only the Blade markup/CSS differ.
     */
    public function roadshow(): View
    {
        return view('roadshow-live', $this->sharedData());
    }

    /**
     * Full-screen roadshow display: identical to /roadshow-live, except idle
     * shows an admin-uploaded image slideshow (full takeover) instead of the
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
