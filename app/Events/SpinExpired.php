<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

// shortcut: Legacy queued broadcasts are ignored. Remove once the old jobs have drained.
class SpinExpired
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $spinSessionId,
        public string $reason = 'expired',
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
