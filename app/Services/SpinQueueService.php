<?php

namespace App\Services;

use App\Events\SpinQueueUpdated;
use App\Models\Campaign;
use App\Models\Player;
use App\Models\SpinQueueEntry;
use Illuminate\Support\Facades\DB;

class SpinQueueService
{
    public function join(Campaign $campaign, Player $player): array
    {
        DB::transaction(function () use ($campaign, $player) {
            $this->prune($campaign);

            $entry = SpinQueueEntry::firstOrCreate(
                ['campaign_id' => $campaign->id, 'player_id' => $player->id],
                ['joined_at' => now(), 'last_seen_at' => now()],
            );

            if (! $entry->wasRecentlyCreated) {
                $entry->forceFill(['last_seen_at' => now()])->save();
            }
        });

        $this->broadcast($campaign);

        return $this->status($campaign, $player, false);
    }

    public function status(Campaign $campaign, Player $player, bool $touch = true): array
    {
        $this->prune($campaign);

        $entry = SpinQueueEntry::query()
            ->where('campaign_id', $campaign->id)
            ->where('player_id', $player->id)
            ->first();

        if ($entry && $touch) {
            $entry->forceFill(['last_seen_at' => now()])->save();
        }

        $position = $entry
            ? SpinQueueEntry::where('campaign_id', $campaign->id)->where('id', '<=', $entry->id)->count()
            : null;

        return [
            'queued' => $entry !== null,
            'position' => $position,
            'ahead' => $position === null ? 0 : max(0, $position - 1),
            'count' => SpinQueueEntry::where('campaign_id', $campaign->id)->count(),
        ];
    }

    public function hasWaiting(Campaign $campaign): bool
    {
        $this->prune($campaign);

        return SpinQueueEntry::where('campaign_id', $campaign->id)->exists();
    }

    public function isFirst(Campaign $campaign, Player $player): bool
    {
        $this->prune($campaign);

        return (int) SpinQueueEntry::where('campaign_id', $campaign->id)->orderBy('id')->value('player_id') === $player->id;
    }

    public function remove(Campaign $campaign, Player $player): void
    {
        $deleted = SpinQueueEntry::where('campaign_id', $campaign->id)
            ->where('player_id', $player->id)
            ->delete();

        if ($deleted) {
            $this->broadcast($campaign);
        }
    }

    public function snapshot(?Campaign $campaign): array
    {
        if (! $campaign) {
            return ['count' => 0, 'players' => []];
        }

        $this->prune($campaign);
        $entries = SpinQueueEntry::with('player')
            ->where('campaign_id', $campaign->id)
            ->orderBy('id')
            ->get();

        return [
            'count' => $entries->count(),
            'players' => $entries->values()->map(fn (SpinQueueEntry $entry, int $index) => [
                'position' => $index + 1,
                'name' => $this->playerName($entry->player),
            ])->all(),
        ];
    }

    public function broadcast(Campaign $campaign): void
    {
        broadcast(new SpinQueueUpdated($this->snapshot($campaign)));
    }

    private function prune(Campaign $campaign): void
    {
        SpinQueueEntry::where('campaign_id', $campaign->id)
            ->where('last_seen_at', '<', now()->subSeconds((int) config('spin.spin.queue_presence_seconds', 120)))
            ->delete();
    }

    private function playerName(?Player $player): string
    {
        if (! $player) {
            return 'Player';
        }

        return trim((string) $player->display_name) ?: $player->email;
    }
}
