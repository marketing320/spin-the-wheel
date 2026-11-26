<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Player;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerExportController extends Controller
{
    private const HEADER_BG = '1F2937';
    private const ACCENT = '84CC16';

    /**
     * Export players to a formatted Excel workbook. Targets either an explicit
     * set of IDs (`ids`) or every player matching the current search (`all=1`).
     * Each player's core profile is followed by one column per active form field.
     */
    public function export(Request $request): StreamedResponse
    {
        $campaign = Campaign::current();
        $fields = $campaign
            ? $campaign->formFields()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        $ids = array_filter(array_map('intval', explode(',', (string) $request->string('ids'))));

        $players = Player::query()
            ->withCount('spinSessions')
            ->with(['formResponses' => fn ($q) => $q->latest()])
            ->when($request->filled('search'), fn ($q) => $q
                ->where(fn ($w) => $w
                    ->where('email', 'like', '%'.$request->string('search').'%')
                    ->orWhere('display_name', 'like', '%'.$request->string('search').'%')))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->latest()
            ->get();

        $headers = [
            'ID', 'Email', 'Display name', 'Verified', 'Email verified at',
            'Form completed at', 'Blocked', 'Spins', 'Last spin', 'Last seen', 'Joined',
        ];
        foreach ($fields as $field) {
            $headers[] = $field->label;
        }

        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('Players');
        $lastCol = $sheet->getCell([count($headers), 1])->getColumn();

        // Title banner.
        $sheet->setCellValue('A1', 'Players Export');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A2', 'Generated '.now()->format('d M Y, H:i').' · '.$players->count().' player(s)'
            .($campaign ? ' · Campaign: '.$campaign->name : ''));
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '6B7280']]]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Header row (row 4).
        $headerRow = 4;
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, $headerRow], $h);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACCENT]]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        // Data rows.
        $campaignId = $campaign?->id;
        $row = $headerRow + 1;
        foreach ($players as $player) {
            $response = $player->formResponses->firstWhere('campaign_id', $campaignId)
                ?? $player->formResponses->first();
            $answers = (array) ($response->responses ?? []);

            $values = [
                $player->id,
                $player->email,
                $player->display_name,
                ($player->otp_verified && $player->email_verified_at) ? 'Yes' : 'No',
                $player->email_verified_at?->format('d M Y H:i'),
                $player->form_completed_at?->format('d M Y H:i'),
                $player->blocked_at ? 'Yes' : 'No',
                $player->spin_sessions_count,
                $player->last_spin_at?->format('d M Y H:i'),
                $player->last_seen_at?->format('d M Y H:i'),
                $player->created_at?->format('d M Y H:i'),
            ];
            foreach ($fields as $field) {
                $value = $answers[$field->field_key] ?? '';
                $values[] = match (true) {
                    is_array($value) => implode(', ', $value),
                    $value === true => 'Yes',
                    $value === false => 'No',
                    default => (string) $value,
                };
            }

            foreach ($values as $i => $v) {
                // Explicit string type keeps phone numbers / invoice numbers intact
                // (no scientific notation, no stripped leading zeros).
                if (is_int($v)) {
                    $sheet->setCellValue([$i + 1, $row], $v);
                } else {
                    $sheet->setCellValueExplicit([$i + 1, $row], (string) ($v ?? ''), DataType::TYPE_STRING);
                }
            }
            $row++;
        }

        $lastRow = max($row - 1, $headerRow);

        // Body styling: borders, zebra stripes, vertical centering.
        $body = "A{$headerRow}:{$lastCol}{$lastRow}";
        $sheet->getStyle($body)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
            if (($r - $headerRow) % 2 === 0) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
            }
        }

        // Centre the short categorical / numeric columns (ID, Verified, Blocked, Spins).
        foreach (['A', 'D', 'G', 'H'] as $col) {
            $sheet->getStyle("{$col}".($headerRow + 1).":{$col}{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Column widths: auto-fit with sensible bounds so nothing shows "#####".
        for ($c = 1; $c <= count($headers); $c++) {
            $dim = $sheet->getColumnDimensionByColumn($c);
            $dim->setAutoSize(true);
        }
        $sheet->calculateColumnWidths();
        for ($c = 1; $c <= count($headers); $c++) {
            $dim = $sheet->getColumnDimensionByColumn($c);
            $dim->setAutoSize(false);
            $dim->setWidth(min(max($dim->getWidth() + 3, 12), 45));
        }

        $sheet->freezePane('C'.($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$lastRow}");
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
        $sheet->getParent()->getProperties()->setTitle('Players Export')->setCreator(config('app.name'));

        $filename = 'players-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet->getParent()))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
