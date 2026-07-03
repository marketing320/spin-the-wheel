<?php

namespace App\Services;

use App\Events\SpinExpired;
use App\Models\SpinSession;
use App\Support\Settings;

/**
 * Owns the global "only one spin at a time" invariant and the failsafe that
 * releases stuck locks. The hard guarantee lives in the database: the unique
 * `active_guard` index on spin_sessions permits at most one active row. This
 * service handles expiry sweeping and lock release around that guard.
 */
class SpinLockService
{
    public function lockTimeoutSeconds(): int
    {
        return (int) Settings::get('spin.lock_timeout_seconds', 45);
    }

    /**
     * Release any active spin whose failsafe window has elapsed. Returns the
     * number of locks released.
     */
    public function expireStale(): int
    {
        $stale = SpinSession::query()
            ->where('status', SpinSession::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($stale as $session) {
            $session->forceFill([
                'status' => SpinSession::STATUS_EXPIRED,
                'active_guard' => null,
            ])->save();

            broadcast(new SpinExpired($session->id, 'timeout'));
        }

        return $stale->count();
    }

    /**
     * The current, non-expired active spin (if any). Sweeps stale locks first.
     */
    public function currentActive(): ?SpinSession
    {
        $this->expireStale();

        return SpinSession::query()
            ->with(['prize', 'player', 'campaign'])
            ->where('status', SpinSession::STATUS_ACTIVE)
            ->latest('started_at')
            ->first();
    }

    public function isLocked(): bool
    {
        return $this->currentActive() !== null;
    }

    /**
     * Release a session's global guard, transitioning it to a terminal status.
     */
    public function release(SpinSession $session, string $status = SpinSession::STATUS_COMPLETED): void
    {
        $session->forceFill([
            'status' => $status,
            'active_guard' => null,
        ])->save();
    }
}
