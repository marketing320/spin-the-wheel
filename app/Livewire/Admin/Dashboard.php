<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\SpinResult;
use App\Models\SpinSession;
use App\Services\SpinLockService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    public function render(SpinLockService $lock)
    {
        $campaign = Campaign::current();

        $winsByPrize = SpinResult::query()
            ->when($campaign, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->selectRaw('prize_id, count(*) as total')
            ->with('prize:id,name,rarity')
            ->groupBy('prize_id')
            ->orderByDesc('total')
            ->get();

        $active = $lock->currentActive();

        return view('livewire.admin.dashboard', [
            'campaign' => $campaign,
            'totalPlayers' => Player::count(),
            'verifiedPlayers' => Player::where('otp_verified', true)->count(),
            'totalSpins' => SpinSession::whereIn('status', [SpinSession::STATUS_COMPLETED, SpinSession::STATUS_ACTIVE])->count(),
            'totalWins' => SpinResult::count(),
            'winsByPrize' => $winsByPrize,
            'activeSpin' => $active,
        ]);
    }
}
