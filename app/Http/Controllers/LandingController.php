<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index(): View
    {
        $campaign = Campaign::current();
        $player = Auth::guard('player')->user();

        return view('landing', [
            'campaign' => $campaign,
            'prizes' => $campaign ? $campaign->activePrizes()->get() : collect(),
            'player' => $player,
            'tagline' => Settings::get('branding.tagline'),
            'appName' => Settings::get('branding.app_name', config('app.name')),
        ]);
    }
}
