<?php

namespace App\Jobs;

use App\Models\SpinSession;
use App\Services\SpinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Failsafe server-side completion. Dispatched with a delay equal to the spin
 * duration so a spin still resolves even if the player closes their browser
 * mid-animation.
 */
class CompleteSpinJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $spinSessionId) {}

    public function handle(SpinService $spins): void
    {
        $session = SpinSession::find($this->spinSessionId);

        if (! $session || $session->status !== SpinSession::STATUS_ACTIVE) {
            return;
        }

        // Only finalise once the spin's animation window has actually elapsed.
        // This keeps the "sync" queue driver (and tests) from completing a spin
        // the instant it starts; the real delayed dispatch fires after ends_at.
        if ($session->ends_at && now()->lt($session->ends_at)) {
            return;
        }

        $spins->complete($session);
    }
}
