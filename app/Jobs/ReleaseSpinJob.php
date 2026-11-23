<?php

namespace App\Jobs;

use App\Models\SpinSession;
use App\Services\SpinLockService;
use App\Services\SpinService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReleaseSpinJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $spinSessionId) {}

    public function handle(SpinService $spins, SpinLockService $lock): void
    {
        $session = SpinSession::find($this->spinSessionId);

        if (! $session || $session->status !== SpinSession::STATUS_ACTIVE) {
            return;
        }

        if ($session->buffer_ends_at && now()->lt($session->buffer_ends_at)) {
            return;
        }

        if (! $session->completed_at) {
            $spins->complete($session);
        }

        $lock->release($session->fresh());
    }
}
