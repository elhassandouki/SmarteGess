<?php

namespace App\Services\Export;

use App\Models\Document;
use App\Models\CompanySetting;
use App\Support\DocumentTypeRegistry;

class ThermalPrinterTicketService
{
    /**
     * Generate thermal printer ticket (80mm width format)
     * Returns ESC/POS formatted string for thermal printers
     */
    public function generateTicketEscPos(Document $document): string
    {
        $document->load([
            'tier',
            'depot',
            'lines.article',
        ]);

        $companySetting = CompanySetting::where('tenant_id', $document->tenant_id)
            ->first();

        $documentType = DocumentTypeRegistry::definitions()[$document->type_document_code] ?? [];

        $ticket = $this->buildTicketString(
            $document,
            $companySetting,
            $documentType
        );

        return $ticket;
    }

    /**
     * Generate ticket HTML for preview/printing
     */
    public function generateTicketHtml(Document $document): string
    {
        $document->load([
            'tier',
            'depot',
            'lines.article',
            'reglements',
        ]);

        $companySetting = CompanySetting::where('tenant_id', $document->tenant_id)
            ->first();

        $documentType = DocumentTypeRegistry::definitions()[$document->type_document_code] ?? [];

        return view('documents.thermal-ticket', [
            'document' => $document,
            'companySetting' => $companySetting,
            'documentLabel' => $documentType['label'] ?? $document->type_document_code,
            'totals' => $this->calculateTotals($document),
        ])->render();
    }

    /**
     * Build ESC/POS ticket string for thermal printer
     * Standard 80mm thermal printer format
     */
    private function buildTicketString(Document $document, ?CompanySetting $companySetting, array $documentType): string
    {
        // ESC/POS commands
        $ESC = chr(27);
        $ticket = '';

        // Initialize
        $ticket .= $ESC . "@"; // Reset
        $ticket .= $ESC . "!" . chr(0); // Normal mode
        $ticket .= $ESC . "a" . chr(1); // Center align

        // Company header
        if ($companySetting?->company_name) {
            $ticket .= $this->centerText($companySetting->company_name, 32) . "\n";
        }

        // Document title
        $title = $documentType['label'] ?? $document->type_document_code;
        $ticket .= $this->centerText($title, 32) . "\n";
        $ticket .= $this->centerText(str_repeat("-", 32), 32) . "\n";

        // Document info
        $ticket .= $ESC . "a" . chr(0); // Center align
        $ticket .= "N°: " . $document->do_piece . "\n";
        $ticket .= "Date: " . optional($document->do_date)->format('d/m/Y H:i') . "\n";
        $ticket .= str_repeat("-", 32) . "\n";

        // Customer/Supplier info
        $ticket .= $ESC . "a" . chr(0); // Left align
        if ($document->tier) {
            $ticket .= "Client/Fournisseur:\n";
            $ticket .= $this->wrapText($document->tier->ct_intitule ?? '', 32) . "\n";
            if ($document->tier->ct_adresse) {
                $ticket .= $this->wrapText($document->tier->ct_adresse, 32) . "\n";
            }
        }

        $ticket .= str_repeat("-", 32) . "\n";

        // Items header
        $ticket .= $this->formatColumnHeader("Article", "Qte", "Prix", 32) . "\n";
        $ticket .= str_repeat("-", 32) . "\n";

        // Items
        $subtotal = 0;
        foreach ($document->lines as $line) {
            $qty = (float) $line->dl_qte;
            $price = (float) $line->dl_prix_unitaire_ht;
            $discount = (float) ($line->dl_remise_percent ?? 0);

            $lineTotal = ($qty * $price) * (1 - ($discount / 100));
            $subtotal += $lineTotal;

            $articleName = $line->article?->ar_design ?? $line->article?->code_article ?? '';
            $ticket .= substr($articleName, 0, 20) . "\n";
            $ticket .= "  " . $qty . "x @ " . number_format($price, 2) . " = " . number_format($lineTotal, 2) . "\n";

            if ($discount > 0) {
                $ticket .= "  Remise: " . $discount . "%\n";
            }
        }

        $ticket .= str_repeat("-", 32) . "\n";

        // Totals
        $tax = (float) $document->do_total_tva ?? 0;
        $total = (float) $document->do_total_ttc ?? 0;

        $ticket .= $this->formatLine("Sous-total", number_format($subtotal, 2), 32) . "\n";
        if ($tax > 0) {
            $ticket .= $this->formatLine("TVA", number_format($tax, 2), 32) . "\n";
        }
        $ticket .= $this->formatLine("TOTAL", number_format($total, 2), 32) . "\n";

        // Payment info
        if ($document->reglements && $document->reglements->count() > 0) {
            $paid = (float) $document->do_montant_regle ?? 0;
            $remaining = max(0, $total - $paid);

            $ticket .= str_repeat("-", 32) . "\n";
            $ticket .= $this->formatLine("Payé", number_format($paid, 2), 32) . "\n";
            if ($remaining > 0) {
                $ticket .= $this->formatLine("Restant dû", number_format($remaining, 2), 32) . "\n";
            }
        }

        // Footer
        $ticket .= str_repeat("-", 32) . "\n";
        $ticket .= $ESC . "a" . chr(1); // Center align
        $ticket .= $this->centerText("Merci de votre visite!", 32) . "\n";
        $ticket .= $this->centerText(date('d/m/Y H:i:s'), 32) . "\n";

        // Cut paper
        $ticket .= $ESC . "m"; // Partial cut

        return $ticket;
    }

    /**
     * Center text for thermal printer
     */
    private function centerText(string $text, int $width): string
    {
        $padding = floor(($width - strlen($text)) / 2);
        return str_repeat(" ", max(0, $padding)) . $text;
    }

    /**
     * Wrap text to specified width
     */
    private function wrapText(string $text, int $width): string
    {
        return wordwrap($text, $width, "\n", true);
    }

    /**
     * Format line with label and value
     */
    private function formatLine(string $label, string $value, int $width): string
    {
        $available = $width - strlen($label) - strlen($value);
        $dots = str_repeat(".", max(0, $available));
        return $label . $dots . $value;
    }

    /**
     * Format column header
     */
    private function formatColumnHeader(string $col1, string $col2, string $col3, int $width): string
    {
        return substr($col1 . str_repeat(" ", 15), 0, 15) .
               substr($col2 . str_repeat(" ", 8), 0, 8) .
               substr(str_repeat(" ", 9) . $col3, -9);
    }

    /**
     * Calculate totals
     */
    private function calculateTotals(Document $document): array
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($document->lines as $line) {
            $quantity = (float) $line->dl_qte;
            $unitPrice = (float) $line->dl_prix_unitaire_ht;
            $discountPercent = (float) ($line->dl_remise_percent ?? 0);

            $lineTotal = $quantity * $unitPrice;
            $discountAmount = ($lineTotal * $discountPercent) / 100;
            $lineHT = $lineTotal - $discountAmount;

            $subtotal += $lineHT;
        }

        return [
            'subtotal' => $subtotal,
            'tax' => (float) $document->do_total_tva ?? 0,
            'total' => (float) $document->do_total_ttc ?? 0,
            'paid' => (float) $document->do_montant_regle ?? 0,
            'remaining' => max(0, ((float) $document->do_total_ttc ?? 0) - ((float) $document->do_montant_regle ?? 0)),
        ];
    }
}
