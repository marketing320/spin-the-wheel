<?php

namespace App\Livewire\Admin;

use App\Models\Player;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin', ['title' => 'Players'])]
class Players extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    public bool $showModal = false;

    public ?int $viewingId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->showModal = true;
    }

    public function toggleBlock(int $id): void
    {
        $player = Player::findOrFail($id);
        $player->forceFill(['blocked_at' => $player->blocked_at ? null : now()])->save();

        session()->flash('status', $player->blocked_at
            ? "{$player->email} has been blocked."
            : "{$player->email} has been unblocked.");
    }

    public function render()
    {
        $players = Player::withCount('spinSessions')
            ->when($this->search, fn ($q) => $q
                ->where('email', 'like', "%{$this->search}%")
                ->orWhere('display_name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(20);

        $viewingPlayer = $this->viewingId
            ? Player::with([
                'formResponses.campaign',
                'spinSessions' => fn ($q) => $q->latest()->limit(10),
            ])->find($this->viewingId)
            : null;

        return view('livewire.admin.players', [
            'players' => $players,
            'viewingPlayer' => $viewingPlayer,
        ]);
    }
}
