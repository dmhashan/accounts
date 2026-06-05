<?php

namespace App\Services;

use App\Jobs\SendDailySummaryReportJob;
use App\Models\DailySummaryReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class DailySummaryReportService
{
    private const ACCOUNT_FIELDS = ['opening_balance', 'income', 'expense'];

    private const STOCK_FIELDS = ['opening', 'received', 'sold'];

    private const FIELD_LABELS = [
        'opening_balance' => 'Opening Balance',
        'income' => 'Income',
        'expense' => 'Expense',
        'opening' => 'Opening Units',
        'received' => 'Received',
        'sold' => 'Sold',
    ];

    public function __construct(
        private readonly DailySummaryService $summaryService,
        private readonly MediaStorageService $media,
    ) {}

    /**
     * Build the editable report payload (system values) for the prepare screen.
     */
    public function prepare(int $tenantId, ?string $date): array
    {
        return $this->summaryService->build($tenantId, $date);
    }

    /**
     * Generate, persist and email a signed daily summary report.
     */
    public function generate(int $tenantId, array $payload, ?int $userId): DailySummaryReport
    {
        $tenant = app('tenant');
        $system = $this->summaryService->build($tenantId, $payload['date'] ?? null);

        [$final, $changes] = $this->applyEdits($system, $payload);

        $reportDate = $system['date'];

        // Store signer images (base64 data URIs from the client).
        $signaturePath = $this->storeDataUriImage(
            $payload['signature'] ?? '',
            'daily-summary',
            'signature-' . $reportDate . '-' . Str::random(6),
        );
        $selfiePath = $this->storeDataUriImage(
            $payload['selfie'] ?? '',
            'daily-summary',
            'selfie-' . $reportDate . '-' . Str::random(6),
        );

        $report = DailySummaryReport::create([
            'tenant_id' => $tenantId,
            'report_date' => $reportDate,
            'prepared_by_user_id' => $userId,
            'prepared_by_name' => trim((string) ($payload['prepared_by_name'] ?? '')),
            'signature_path' => $signaturePath,
            'selfie_path' => $selfiePath,
            'system_snapshot' => $system,
            'final_snapshot' => $final,
            'changes' => $changes,
            'totals' => $final['totals'],
        ]);

        $pdfContent = $this->renderPdf($report, $tenant);
        $filename = 'daily-summary-' . $reportDate . '-' . $report->id . '.pdf';
        $pdfPath = $this->media->storeContent($pdfContent, "daily-summary/{$filename}");

        $report->update(['pdf_path' => $pdfPath]);

        SendDailySummaryReportJob::dispatch($tenantId, $report->id);

        return $report->fresh();
    }

    // ───────────────────────── History ──────────────────────────────────────

    public function history(int $tenantId, int $perPage): array
    {
        $reports = DailySummaryReport::where('tenant_id', $tenantId)
            ->with('preparedBy:id,name')
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'data' => collect($reports->items())->map(fn ($r) => $this->serialize($r)),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ];
    }

    public function show(DailySummaryReport $report): array
    {
        $report->loadMissing('preparedBy:id,name');

        return array_merge($this->serialize($report), [
            'system_snapshot' => $report->system_snapshot,
            'final_snapshot' => $report->final_snapshot,
            'changes' => $report->changes ?? [],
            'signature_url' => $report->signature_path ? $this->media->url($report->signature_path) : null,
            'selfie_url' => $report->selfie_path ? $this->media->url($report->selfie_path) : null,
        ]);
    }

    public function pdfContents(DailySummaryReport $report): ?string
    {
        if (!$report->pdf_path) {
            return null;
        }

        $disk = config('filesystems.media_disk', 'public');

        return Storage::disk($disk)->get($report->pdf_path) ?: null;
    }

    // ───────────────────────── Edit application ─────────────────────────────

    private function applyEdits(array $system, array $payload): array
    {
        $final = $system;
        $changes = [];

        $accountEdits = collect($payload['accounts'] ?? [])->keyBy('id');
        $stockEdits = collect($payload['stock'] ?? [])->keyBy('product_id');

        foreach ($final['accounts'] as $i => $account) {
            $edit = $accountEdits->get($account['id']);

            if ($edit) {
                foreach (self::ACCOUNT_FIELDS as $field) {
                    if (!array_key_exists($field, $edit)) {
                        continue;
                    }
                    $new = round((float) $edit[$field], 2);
                    $old = round((float) $account[$field], 2);

                    if (abs($new - $old) > 0.001) {
                        $changes[] = [
                            'section' => 'Account',
                            'ref' => $account['name'],
                            'field' => self::FIELD_LABELS[$field],
                            'original' => $old,
                            'edited' => $new,
                            'is_money' => true,
                        ];
                        $final['accounts'][$i][$field] = $new;
                        $final['accounts'][$i]['edited'][$field] = true;
                    }
                }
            }

            $final['accounts'][$i]['closing_balance'] = round(
                (float) $final['accounts'][$i]['opening_balance']
                + (float) $final['accounts'][$i]['income']
                - (float) $final['accounts'][$i]['expense'],
                2,
            );
        }

        foreach ($final['stock']['movements'] as $i => $item) {
            $edit = $stockEdits->get($item['product_id']);

            if ($edit) {
                foreach (self::STOCK_FIELDS as $field) {
                    if (!array_key_exists($field, $edit)) {
                        continue;
                    }
                    $new = round((float) $edit[$field], 2);
                    $old = round((float) $item[$field], 2);

                    if (abs($new - $old) > 0.001) {
                        $changes[] = [
                            'section' => 'Stock',
                            'ref' => $item['product_name'],
                            'field' => self::FIELD_LABELS[$field],
                            'original' => $old,
                            'edited' => $new,
                            'is_money' => false,
                        ];
                        $final['stock']['movements'][$i][$field] = $new;
                        $final['stock']['movements'][$i]['edited'][$field] = true;
                    }
                }
            }

            $final['stock']['movements'][$i]['closing'] = round(
                (float) $final['stock']['movements'][$i]['opening']
                + (float) $final['stock']['movements'][$i]['received']
                - (float) $final['stock']['movements'][$i]['sold'],
                2,
            );
        }

        $final['totals'] = $this->recomputeTotals($final);
        $final['stock']['totals'] = $this->recomputeStockTotals($final['stock']['movements']);

        return [$final, $changes];
    }

    private function recomputeTotals(array $final): array
    {
        $accounts = collect($final['accounts']);
        $opening = round($accounts->sum('opening_balance'), 2);
        $income = round($accounts->sum('income'), 2);
        $expense = round($accounts->sum('expense'), 2);

        return [
            'opening_balance' => $opening,
            'income' => $income,
            'expense' => $expense,
            'closing_balance' => round($opening + $income - $expense, 2),
            'net_movement' => round($income - $expense, 2),
            'stock_on_hand' => $final['totals']['stock_on_hand'] ?? 0,
        ];
    }

    private function recomputeStockTotals(array $movements): array
    {
        $rows = collect($movements);

        return [
            'opening' => round($rows->sum('opening'), 2),
            'received' => round($rows->sum('received'), 2),
            'sold' => round($rows->sum('sold'), 2),
            'closing' => round($rows->sum('closing'), 2),
            'revenue' => round($rows->sum('revenue'), 2),
        ];
    }

    // ───────────────────────── PDF rendering ────────────────────────────────

    private function renderPdf(DailySummaryReport $report, $tenant): string
    {
        $html = view('pdfs.daily-summary', [
            'report' => $report,
            'final' => $report->final_snapshot,
            'changes' => $report->changes ?? [],
            'tenantName' => $tenant->name ?? '',
            'tenantAddress' => $tenant->address ?? '',
            'tenantEmail' => $tenant->email ?? '',
            'tenantPhone' => $tenant->phone ?? '',
            'tenantLogo' => $this->imageToDataUri($tenant->logo_path ?? null),
            'signatureImg' => $this->imageToDataUri($report->signature_path),
            'selfieImg' => $this->imageToDataUri($report->selfie_path),
            'generatedAt' => now()->format('d M Y, H:i'),
        ])->render();

        $defaultFontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $defaultFontData = (new FontVariables)->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'fontDir' => array_merge($defaultFontDirs, [storage_path('fonts')]),
            'fontdata' => $defaultFontData,
            'default_font' => 'dejavusans',
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    // ───────────────────────── Helpers ──────────────────────────────────────

    private function serialize(DailySummaryReport $report): array
    {
        return [
            'id' => $report->id,
            'report_date' => $report->report_date->toDateString(),
            'date_label' => $report->report_date->format('d M Y'),
            'prepared_by_name' => $report->prepared_by_name,
            'prepared_by_user' => $report->preparedBy?->name,
            'change_count' => is_array($report->changes) ? count($report->changes) : 0,
            'totals' => $report->totals,
            'has_pdf' => (bool) $report->pdf_path,
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    private function storeDataUriImage(string $dataUri, string $directory, string $baseName): ?string
    {
        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/i', $dataUri, $m)) {
            return null;
        }

        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $encoded = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return null;
        }

        return $this->media->storeContent($binary, "{$directory}/{$baseName}.{$ext}");
    }

    private function imageToDataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        try {
            $disk = config('filesystems.media_disk', 'public');
            $content = Storage::disk($disk)->get($path);

            if (!$content) {
                return null;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            ];
            $mime = $mimeMap[$ext] ?? 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        } catch (\Throwable) {
            return null;
        }
    }
}
