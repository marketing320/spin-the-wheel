<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Admin / back-office user. The `is_admin` flag gates the full admin panel;
 * `is_staff` gates only the limited staff surface (dashboard, spin history,
 * voucher redemption) — see EnsureAdmin / EnsureStaffAccess middleware.
 */
#[Fillable(['name', 'email', 'password', 'is_admin', 'is_staff'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_staff' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isStaff(): bool
    {
        return (bool) $this->is_staff;
    }

    /**
     * Whether this user may access the limited staff surface (redemption,
     * dashboard, spin history). Admins are always a superset of staff.
     */
    public function canAccessStaffTools(): bool
    {
        return $this->isAdmin() || $this->isStaff();
    }
}
