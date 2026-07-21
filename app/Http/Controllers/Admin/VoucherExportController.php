<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of vouchers, honouring the same status filter + search as the
 * admin Vouchers page. Admin-only (customer details are unmasked here).
 */
class VoucherExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $query = Voucher::query()
            ->with(['prize:id,name,rarity', 'player:id,email,display_name', 'campaign:id,name', 'redeemedByUser:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(function ($w) use ($term) {
                    $w->where('code', 'like', "%{$term}%")
                        ->orWhereHas('prize', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('player', fn ($p) => $p
                            ->where('email', 'like', "%{$term}%")
                            ->orWhere('display_name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('filter'), function ($q) use ($request) {
                // Same scopes the admin Vouchers tabs use, so an export matches
                // exactly what was on screen.
                match ($request->string('filter')->value()) {
                    'pending' => $q->stillActive(),
                    'redeemed' => $q->redeemed(),
                    'expired' => $q->expiredUnused(),
                    'rotated' => $q->rotated(),
                    default => $q,
                };
            })
            ->latest();

        $filename = 'vouchers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Campaign', 'Prize', 'Rarity', 'Email', 'Display name', 'Status', 'Expires at', 'Redeemed at', 'Redeemed by', 'Rotated at']);

            $query->chunk(500, function ($vouchers) use ($out) {
                foreach ($vouchers as $v) {
                    fputcsv($out, [
                        $v->code,
                        $v->campaign?->name,
                        $v->prize?->name,
                        $v->prize?->rarity,
                        $v->player?->email,
                        $v->player?->display_name,
                        $v->displayStatus(),
                        $v->expires_at?->toDateTimeString(),
                        $v->redeemed_at?->toDateTimeString(),
                        $v->redeemedByUser?->name,
                        $v->rotated_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
