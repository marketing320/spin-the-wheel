<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpinSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpinExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $query = SpinSession::query()
            ->with(['player:id,email,display_name', 'prize:id,name,rarity', 'campaign:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->whereHas('player', fn ($p) => $p->where('email', 'like', "%{$term}%"));
            })
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->integer('campaign_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        $filename = 'spins-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Campaign', 'Email', 'Display name', 'Prize', 'Rarity', 'Status', 'Started at', 'Completed at']);

            $query->chunk(500, function ($sessions) use ($out) {
                foreach ($sessions as $s) {
                    fputcsv($out, [
                        $s->id,
                        $s->campaign?->name,
                        $s->player?->email,
                        $s->player?->display_name,
                        $s->prize?->name,
                        $s->prize?->rarity,
                        $s->status,
                        $s->started_at?->toDateTimeString(),
                        $s->completed_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
