<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\Prize;
use App\Models\SpinSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin', ['title' => 'Spin History'])]
class Spins extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $campaignId = null;

    #[Url]
    public ?int $prizeId = null;

    #[Url]
    public ?string $status = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCampaignId(): void
    {
        // Prizes belong to a campaign, so a prize picked under the old campaign
        // would filter to nothing — clear it when the campaign changes.
        $this->prizeId = null;
        $this->resetPage();
    }

    public function updatingPrizeId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        // Prize picker: scoped to the chosen campaign, else every prize.
        $prizes = Prize::query()
            ->with('campaign:id,name')
            ->when($this->campaignId, fn ($q) => $q->where('campaign_id', $this->campaignId))
            ->orderBy('name')
            ->get(['id', 'name', 'campaign_id']);

        $spins = SpinSession::query()
            ->with([
                'player:id,email,display_name',
                'prize:id,name,rarity',
                'campaign:id,name',
            ])
            ->when($this->search, fn ($q) => $q->whereHas(
                'player',
                // Group the OR so it stays inside the relationship correlation.
                fn ($p) => $p->where(fn ($w) => $w
                    ->where('email', 'like', "%{$this->search}%")
                    ->orWhere('display_name', 'like', "%{$this->search}%"))
            ))
            ->when($this->campaignId, fn ($q) => $q->where('campaign_id', $this->campaignId))
            ->when($this->prizeId, fn ($q) => $q->where('prize_id', $this->prizeId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.spins', [
            'spins' => $spins,
            'campaigns' => $campaigns,
            'prizes' => $prizes,
            // Only disambiguate prize names with their campaign when the list
            // can span multiple campaigns.
            'showPrizeCampaign' => ! $this->campaignId && $campaigns->count() > 1,
            'statuses' => ['pending', 'active', 'completed', 'expired', 'failed'],
        ]);
    }
}
