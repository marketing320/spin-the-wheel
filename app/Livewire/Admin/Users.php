<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignResetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin-only management of back-office accounts (the sales team + fellow
 * admins): create, edit, delete, set/reset passwords, and toggle the
 * admin/staff role flags. Also hosts the destructive "reset campaign data"
 * tool used to clear test data from a production instance.
 */
#[Layout('components.layouts.admin', ['title' => 'Users'])]
class Users extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    // Create / edit modal.
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_admin = false;

    public bool $is_staff = true;

    // Destructive campaign-reset modal.
    public bool $showResetModal = false;

    public string $resetConfirm = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            // Required when creating; on edit a blank field keeps the current
            // password. Either way, if provided it must be confirmed + strong.
            'password' => [$this->editingId ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'is_admin' => 'boolean',
            'is_staff' => 'boolean',
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation']);
        $this->is_admin = false;
        $this->is_staff = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_admin = (bool) $user->is_admin;
        $this->is_staff = (bool) $user->is_staff;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Every back-office account needs at least one role, otherwise it can
        // authenticate but land on a 403 everywhere (see Login::canAccess…).
        if (! $this->is_admin && ! $this->is_staff) {
            $this->addError('is_staff', 'Select at least one role (Admin or Staff).');

            return;
        }

        // Never let the last admin demote themselves out of existence.
        if ($this->editingId && ! $this->is_admin) {
            $editing = User::find($this->editingId);

            if ($editing && $editing->is_admin && $this->isLastAdmin($editing->id)) {
                $this->addError('is_admin', 'You cannot remove the last remaining admin.');

                return;
            }
        }

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $this->is_admin,
            'is_staff' => $this->is_staff,
        ];

        // The model's `hashed` cast hashes this on write. Only touch it when a
        // value was supplied so editing without a new password keeps the old one.
        if (filled($this->password)) {
            $attributes['password'] = $this->password;
        }

        User::updateOrCreate(['id' => $this->editingId], $attributes);

        $this->showModal = false;
        $this->dispatch('admin-toast', message: $this->editingId ? 'User updated.' : 'User created.');
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::guard('web')->id()) {
            $this->dispatch('admin-toast', message: 'You cannot delete your own account.');

            return;
        }

        if ($user->is_admin && $this->isLastAdmin($user->id)) {
            $this->dispatch('admin-toast', message: 'You cannot delete the last remaining admin.');

            return;
        }

        // vouchers.redeemed_by is nullOnDelete, so historical redemptions are
        // preserved with a null redeemer rather than blocking the delete.
        $user->delete();

        $this->dispatch('admin-toast', message: 'User deleted.');
    }

    public function confirmReset(): void
    {
        $this->reset('resetConfirm');
        $this->resetValidation();
        $this->showResetModal = true;
    }

    public function resetCampaign(): void
    {
        if (mb_strtoupper(trim($this->resetConfirm)) !== 'RESET') {
            $this->addError('resetConfirm', 'Type RESET to confirm.');

            return;
        }

        $stats = app(CampaignResetService::class)->reset(Campaign::current());

        $this->reset('resetConfirm');
        $this->showResetModal = false;

        $players = $stats['players'] ?? 0;
        $this->dispatch('admin-toast', message: "Campaign reset — cleared {$players} player(s) and all play history.");
    }

    private function isLastAdmin(int $excludeId): bool
    {
        return User::query()
            ->where('is_admin', true)
            ->where('id', '!=', $excludeId)
            ->doesntExist();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.users', [
            'users' => $users,
            'currentUserId' => Auth::guard('web')->id(),
            'activeCampaign' => Campaign::current(),
        ]);
    }
}
