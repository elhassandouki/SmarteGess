<?php

namespace App\Services\Export;

use App\Models\Document;
use App\Models\CompanySetting;
use App\Support\DocumentTypeRegistry;

class InvoicePdfService
{
    public function __construct(
        private readonly PdfRendererService $pdfRendererService
    ) {
    }

    /**
     * Generate detailed invoice PDF for any document type
     */
    public function generateInvoicePdf(Document $document)
    {
        $document->load([
            'tier',
            'depot',
            'transporteur',
            'lines.article',
            'reglements'
        ]);

        $companySetting = CompanySetting::where('tenant_id', $document->tenant_id)
            ->first();

        $documentType = DocumentTypeRegistry::definitions()[$document->type_document_code] ?? [];

        $data = [
            'document' => $document,
            'companySetting' => $companySetting,
            'documentLabel' => $documentType['label'] ?? $document->type_document_code,
            'documentFlow' => $documentType['flow'] ?? 'document',
            'calculations' => $this->calculateTotals($document),
            'payments' => $document->reglements ?? [],
        ];

        return $this->pdfRendererService->download(
            'documents.invoice-pdf',
            $data,
            'invoice-' . $document->do_piece . '.pdf'
        );
    }

    /**
     * Calculate detailed totals and tax information
     */
    private function calculateTotals(Document $document): array
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

            // Get tax rate from article if available
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
