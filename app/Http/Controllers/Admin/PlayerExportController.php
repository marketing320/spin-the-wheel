<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Player;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerExportController extends Controller
{
    /**
     * Export players to CSV. Targets either an explicit set of IDs (`ids`) or
     * every player matching the current search (`all=1`). Each player's core
     * profile is followed by one column per active-campaign form field.
     */
    public function export(Request $request): StreamedResponse
    {
        $campaign = Campaign::current();
        $fields = $campaign
            ? $campaign->formFields()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        $ids = array_filter(array_map('intval', explode(',', (string) $request->string('ids'))));

        $query = Player::query()
            ->withCount('spinSessions')
            ->with(['formResponses' => fn ($q) => $q->latest()])
            ->when($request->filled('search'), fn ($q) => $q
                ->where('email', 'like', '%'.$request->string('search').'%')
                ->orWhere('display_name', 'like', '%'.$request->string('search').'%'))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->latest();

        $filename = 'players-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $fields, $campaign) {
            $out = fopen('php://output', 'w');

            $header = [
                'ID', 'Email', 'Display name', 'Verified', 'Email verified at',
                'Form completed at', 'Blocked', 'Spins', 'Last spin', 'Last seen', 'Joined',
            ];
            foreach ($fields as $field) {
                $header[] = $field->label;
            }
            fputcsv($out, $header);

            $campaignId = $campaign?->id;

            $query->chunk(500, function ($players) use ($out, $fields, $campaignId) {
                foreach ($players as $player) {
                    // Prefer the response for the active campaign, else the latest.
                    $response = $player->formResponses->firstWhere('campaign_id', $campaignId)
                        ?? $player->formResponses->first();
                    $answers = (array) ($response->responses ?? []);

                    $row = [
                        $player->id,
                        $player->email,
                        $player->display_name,
                        ($player->otp_verified && $player->email_verified_at) ? 'Yes' : 'No',
                        $player->email_verified_at?->toDateTimeString(),
                        $player->form_completed_at?->toDateTimeString(),
                        $player->blocked_at ? 'Yes' : 'No',
                        $player->spin_sessions_count,
                        $player->last_spin_at?->toDateTimeString(),
                        $player->last_seen_at?->toDateTimeString(),
                        $player->created_at?->toDateTimeString(),
                    ];

                    foreach ($fields as $field) {
                        $value = $answers[$field->field_key] ?? '';
                        $row[] = match (true) {
                            is_array($value) => implode(', ', $value),
                            $value === true => 'Yes',
                            $value === false => 'No',
                            default => (string) $value,
                        };
                    }

                    fputcsv($out, $row);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
