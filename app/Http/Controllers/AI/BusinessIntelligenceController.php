<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AI\BusinessAnalyticsService;
use App\Services\AI\StockIntelligenceService;
use App\Services\Export\DocumentExportService;
use App\Services\PdfRendererService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessIntelligenceController extends Controller
{
    public function __construct(
        private readonly BusinessAnalyticsService $analyticsService,
        private readonly StockIntelligenceService $stockIntelligenceService,
        private readonly DocumentExportService $documentExportService,
        private readonly PdfRendererService $pdfRendererService
    ) {
    }

    public function index()
    {
        return view('ai.index');
    }

    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report' => ['required', 'in:revenue_by_customer,stock_valuation,outstanding_receivables,period_comparison'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $from = Carbon::parse($validated['date_from'] ?? now()->subDays(30)->toDateString())->startOfDay();
        $to = Carbon::parse($validated['date_to'] ?? now()->toDateString())->endOfDay();

        $payload = match ($validated['report']) {
            'revenue_by_customer' => $this->analyticsService->revenueByCustomer($from, $to),
            'stock_valuation' => $this->analyticsService->stockValuationByArticle(),
            'outstanding_receivables' => $this->analyticsService->outstandingReceivables(),
            'period_comparison' => $this->analyticsService->periodComparison($from, $to),
        };

        return response()->json([
            'report' => $validated['report'],
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'data' => $payload,
        ]);
    }

    public function stockAlerts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lookback_days' => ['nullable', 'integer', 'min:7', 'max:365'],
            'coverage_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $payload = $this->stockIntelligenceService->alerts(
            (int) ($validated['lookback_days'] ?? 90),
            (int) ($validated['coverage_days'] ?? 14)
        );

        return response()->json($payload);
    }

    public function exportDocumentPdf(Document $document)
    {
        return $this->documentExportService->exportDocumentPdf($document);
    }

    public function exportDocumentExcel(Document $document)
    {
        return $this->documentExportService->exportDocumentExcel($document);
    }

    public function exportReport(Request $request, string $format)
    {
        $validated = $request->validate([
            'report' => ['required', 'in:revenue_by_customer,stock_valuation,outstanding_receivables,period_comparison'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $from = Carbon::parse($validated['date_from'] ?? now()->subDays(30)->toDateString())->startOfDay();
        $to = Carbon::parse($validated['date_to'] ?? now()->toDateString())->endOfDay();
        $reportName = $validated['report'];
        $data = $this->report($request)->getData(true);

        if ($format === 'excel') {
            return response()->streamDownload(function () use ($data): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['report', $data['report']]);
                fputcsv($handle, ['from', $data['range']['from']]);
                fputcsv($handle, ['to', $data['range']['to']]);
                fputcsv($handle, []);
                fputcsv($handle, ['json_payload']);
                fputcsv($handle, [json_encode($data['data'], JSON_UNESCAPED_UNICODE)]);
                fclose($handle);
            }, $reportName.'-'.$from->toDateString().'-'.$to->toDateString().'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return $this->pdfRendererService->download(
            'ai.report-pdf',
            [
                'title' => $reportName,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'payload' => $data['data'],
            ],
            $reportName.'-'.$from->toDateString().'-'.$to->toDateString().'.pdf'
        );
    }
}

