<?php

namespace App\Livewire\Player;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * "My Prizes" — a logged-in player's own spin history, including any
 * vouchers (code/QR/barcode/status/expiry) they've won.
 */
#[Layout('components.layouts.app')]
class PastPrizes extends Component
{
    use WithPagination;

    public function render()
    {
        $player = Auth::guard('player')->user();

        $results = $player->spinResults()
            ->with(['prize', 'voucher', 'campaign'])
            ->latest()
            ->paginate(10);

        return view('livewire.player.past-prizes', [
            'results' => $results,
        ]);
    }
}
