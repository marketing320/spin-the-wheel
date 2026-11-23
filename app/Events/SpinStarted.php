<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

// shortcut: Legacy queued broadcasts are ignored. Remove once the old jobs have drained.
class SpinStarted
{
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
