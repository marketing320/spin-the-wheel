<?php

namespace App\Livewire\Admin;

use App\Models\Voucher;
use App\Services\VoucherRotationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Voucher management + history. Accessible to staff and admins, but the two
 * see different things: admins get full customer details plus the rotate /
 * bulk-rotate / CSV-export tools; staff get a read-only, privacy-masked list
 * (their day-to-day use is the "Redeemed" history tab).
 */
#[Layout('components.layouts.admin', ['title' => 'Vouchers'])]
class Vouchers extends Component
{
    use WithPagination;

    private const FILTERS = ['all', 'pending', 'redeemed', 'expired', 'rotated'];

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, self::FILTERS, true) ? $filter : 'all';
        $this->resetPage();
    }

    public function rotate(int $id): void
    {
        $this->authorizeAdmin();

        $voucher = Voucher::find($id);

        if (! $voucher) {
            return;
        }

        $rotated = app(VoucherRotationService::class)->rotate($voucher);

        $this->dispatch('admin-toast', message: $rotated
            ? "Voucher {$voucher->code} rotated back onto the wheel."
            : 'That voucher is no longer eligible to rotate.');
    }

    public function rotateAllExpired(): void
    {
        $this->authorizeAdmin();

        // Global (all campaigns) to match the count shown on the button. The
        // scheduled `vouchers:rotate` command instead defaults to the active
        // campaign (with --all) for routine automation.
        $count = app(VoucherRotationService::class)->rotateExpiredUnused();

        $this->resetPage();
        $this->dispatch('admin-toast', message: "Rotated {$count} expired voucher(s) back onto the wheel.");
    }

    private function authorizeAdmin(): void
    {
        abort_unless((bool) Auth::guard('web')->user()?->isAdmin(), 403);
    }

    /**
     * Apply the active status filter using the shared Voucher scopes, so the
     * tabs match the counts, the CSV export, and rotation eligibility exactly.
     * Lazy expiry (a still-"pending" row past expires_at) and the redeemed_at
     * source-of-truth rule are both resolved inside those scopes.
     */
    private function applyFilter($query): void
    {
        match ($this->filter) {
            'pending' => $query->stillActive(),
            'redeemed' => $query->redeemed(),
            'expired' => $query->expiredUnused(),
            'rotated' => $query->rotated(),
            default => $query,
        };
    }

    private function baseQuery()
    {
        $query = Voucher::query()->when($this->search, function ($q) {
            $term = trim($this->search);
            $q->where(function ($w) use ($term) {
                $w->where('code', 'like', "%{$term}%")
                    ->orWhereHas('prize', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('player', fn ($p) => $p
                        ->where('email', 'like', "%{$term}%")
                        ->orWhere('display_name', 'like', "%{$term}%"));
            });
        });

        $this->applyFilter($query);

        return $query;
    }

    public function render()
    {
        $counts = [
            'all' => Voucher::count(),
            'pending' => Voucher::stillActive()->count(),
            'redeemed' => Voucher::redeemed()->count(),
            'expired' => Voucher::expiredUnused()->count(),
            'rotated' => Voucher::rotated()->count(),
        ];

        $vouchers = $this->baseQuery()
            ->with([
                'prize:id,name,rarity',
                'player:id,email,display_name',
                'campaign:id,name',
                'redeemedByUser:id,name',
            ])
            ->latest()
            ->paginate(20);

        return view('livewire.admin.vouchers', [
            'vouchers' => $vouchers,
            'counts' => $counts,
            'isAdmin' => (bool) Auth::guard('web')->user()?->isAdmin(),
            'exportParams' => array_filter(['filter' => $this->filter, 'search' => $this->search]),
        ]);
    }
}
