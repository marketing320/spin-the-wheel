<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SpinSession;
use App\Services\SpinEligibilityService;
use App\Services\SpinLockService;
use App\Services\WheelAnimationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class SpinController extends Controller
{
    public function __construct(
        protected WheelAnimationService $animation,
        protected SpinEligibilityService $eligibility,
        protected SpinLockService $lock,
    ) {}

    public function index(): View
    {
        $player = Auth::guard('player')->user();
        $campaign = Campaign::current();

        abort_if(! $campaign, 404, 'No active campaign.');

        $segments = $this->animation->segments($campaign);
        $eligibility = $this->eligibility->check($player, $campaign);
        $active = $this->lock->currentActive();

        return view('spin', [
            'campaign' => $campaign,
            'player' => $player,
            'segments' => $segments,
            'eligibility' => $eligibility,
            'geofenceEnabled' => (bool) ($campaign->geofenceSetting?->enabled),
            'spinInProgress' => $active !== null && $active->player_id !== $player->id,
        ]);
    }

    public function result(SpinSession $spin): View
    {
        $player = Auth::guard('player')->user();

        abort_if($spin->player_id !== $player->id, 403);

        $spin->load(['prize', 'campaign']);

        return view('result', [
            'spin' => $spin,
            'prize' => $spin->prize,
        ]);
    }
}
