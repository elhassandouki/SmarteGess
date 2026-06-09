<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Export\InvoicePdfService;
use App\Services\Export\ThermalPrinterTicketService;
use Illuminate\Auth\Access\AuthorizationException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoicePdfService $invoicePdfService,
        private readonly ThermalPrinterTicketService $thermalPrinterTicketService
    ) {
        $this->middleware('auth');
    }

    /**
     * Generate and download detailed invoice PDF
     * Supports all document types: Devis, BC, BL, Facture, etc.
     */
    public function downloadInvoicePdf(Document $document)
    {
        try {
            $this->authorize('view', $document);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->invoicePdfService->generateInvoicePdf($document);
    }

    /**
     * Generate thermal printer ticket (HTML format for printing)
     */
    public function printThermalTicket(Document $document)
    {
        try {
            $this->authorize('view', $document);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $html = $this->thermalPrinterTicketService->generateTicketHtml($document);

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="ticket-' . $document->do_piece . '.html"');
    }

    /**
     * Get thermal printer ticket in ESC/POS format
     * For direct thermal printer integration
     */
    public function getThermalTicketEscPos(Document $document)
    {
        try {
            $this->authorize('view', $document);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $escPos = $this->thermalPrinterTicketService->generateTicketEscPos($document);

        return response($escPos)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="ticket-' . $document->do_piece . '.bin"');
    }

    /**
     * Preview invoice PDF in browser
     */
    public function previewInvoicePdf(Document $document)
    {
        try {
            $this->authorize('view', $document);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $document->load([
            'tier',
            'depot',
            'transporteur',
            'lines.article',
            'reglements'
        ]);

        $companySetting = \App\Models\CompanySetting::where('tenant_id', $document->tenant_id)
            ->first();

        $documentType = \App\Support\DocumentTypeRegistry::definitions()[$document->type_document_code] ?? [];

        // Use a service to get calculations
        $service = new InvoicePdfService(new \App\Services\Export\PdfRendererService());
        $calculations = $this->getCalculations($document);

        return view('documents.invoice-pdf', [
            'document' => $document,
            'companySetting' => $companySetting,
            'documentLabel' => $documentType['label'] ?? $document->type_document_code,
            'calculations' => $calculations,
        ]);
    }

    /**
     * Preview thermal ticket in browser
     */
    public function previewThermalTicket(Document $document)
    {
        try {
            $this->authorize('view', $document);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $document->load([
            'tier',
            'depot',
            'lines.article',
            'reglements',
        ]);

        $companySetting = \App\Models\CompanySetting::where('tenant_id', $document->tenant_id)
            ->first();

        $documentType = \App\Support\DocumentTypeRegistry::definitions()[$document->type_document_code] ?? [];

        $totals = $this->getCalculations($document);

        return view('documents.thermal-ticket', [
            'document' => $document,
            'companySetting' => $companySetting,
            'documentLabel' => $documentType['label'] ?? $document->type_document_code,
            'totals' => $totals,
        ]);
    }

    /**
     * Calculate totals for a document
     */
    private function getCalculations(Document $document): array
    {
        $subtotal = 0;
        $totalTax = 0;
        $lineDetails = [];

        foreach ($document->lines as $line) {
            $quantity = (float) $line->dl_qte;
            $unitPrice = (float) $line->dl_prix_unitaire_ht;
            $discountPercent = (float) ($line->dl_remise_percent ?? 0);

            $lineTotal = $quantity * $unitPrice;
            $discountAmount = ($lineTotal * $discountPercent) / 100;
            $lineHT = $lineTotal - $discountAmount;

            $taxRate = $line->article?->tax?->tax_rate ?? 0;
            $lineTax = ($lineHT * $taxRate) / 100;
            $lineTTC = $lineHT + $lineTax;

            $lineDetails[] = [
                'article' => $line->article,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'lineTotal' => $lineTotal,
                'discountPercent' => $discountPercent,
                'discountAmount' => $discountAmount,
                'lineHT' => $lineHT,
                'taxRate' => $taxRate,
                'lineTax' => $lineTax,
                'lineTTC' => $lineTTC,
            ];

            $subtotal += $lineHT;
            $totalTax += $lineTax;
        }

        return [
            'lineDetails' => $lineDetails,
            'subtotal' => $subtotal,
            'totalTax' => $totalTax,
            'totalTTC' => $subtotal + $totalTax,
            'paid' => (float) $document->do_montant_regle ?? 0,
            'remaining' => max(0, ($subtotal + $totalTax) - ((float) $document->do_montant_regle ?? 0)),
        ];
    }
}
