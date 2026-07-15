<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Self-service password change for any authenticated back-office user (admin
 * or sales-team staff). Requires the current password before rotating it.
 */
#[Layout('components.layouts.admin', ['title' => 'Change Password'])]
class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ];
    }

    protected function messages(): array
    {
        return [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a password different from your current one.',
        ];
    }

    public function updatePassword(): void
    {
        $this->validate();

        Auth::guard('web')->user()
            ->forceFill(['password' => Hash::make($this->password)])
            ->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('admin-toast', message: 'Password updated.');
    }

    public function render()
    {
        return view('livewire.admin.change-password');
    }
}
