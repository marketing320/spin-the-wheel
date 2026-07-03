<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
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
    public ?string $status = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCampaignId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $spins = SpinSession::query()
            ->with([
                'player:id,email,display_name',
                'prize:id,name,rarity',
                'campaign:id,name',
            ])
            ->when($this->search, fn ($q) => $q->whereHas(
                'player',
                fn ($p) => $p->where('email', 'like', "%{$this->search}%")
            ))
            ->when($this->campaignId, fn ($q) => $q->where('campaign_id', $this->campaignId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.spins', [
            'spins' => $spins,
            'campaigns' => Campaign::orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'active', 'completed', 'expired', 'failed'],
        ]);
    }
}
