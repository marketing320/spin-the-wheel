<?php

namespace Tests\Unit;

use App\Events\SpinCompleted;
use App\Events\SpinExpired;
use App\Events\SpinQueueUpdated;
use App\Events\SpinStarted;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Factory;
use PHPUnit\Framework\TestCase;

class RetiredSpinBroadcastTest extends TestCase
{
    public function test_queued_spin_events_finish_without_broadcasting(): void
    {
        $broadcaster = $this->createMock(Factory::class);
        $broadcaster->expects($this->never())->method('connection');

        foreach ([new SpinStarted([]), new SpinCompleted([]), new SpinExpired(1), new SpinQueueUpdated([])] as $event) {
            $job = unserialize(serialize(new BroadcastEvent($event)));
            $job->handle($broadcaster);
        }
    }
}
