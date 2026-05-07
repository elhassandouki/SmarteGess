<?php

namespace App\Services\ERP;

use App\Models\Article;
use App\Models\Document;
use App\Support\DocumentTypeRegistry;

class DocumentWorkflowService
{
    public function buildHeaderData(array $data): array
    {
        $definition = DocumentTypeRegistry::definitions()[$data['type_document_code']] ?? null;
        $taxRates = $this->articleTaxRates(collect($data['lines'])->pluck('article_id')->all());

        $totals = collect($data['lines'])->reduce(function (array $carry, array $line) use ($taxRates) {
            $lineTotalHt = $this->lineTotalHt($line);
            $taxRate = (float) ($taxRates[(int) $line['article_id']] ?? 0);
            $lineTotalTva = $lineTotalHt * ($taxRate / 100);

            $carry['ht'] += $lineTotalHt;
            $carry['tva'] += $lineTotalTva;
            $carry['ttc'] += $lineTotalHt + $lineTotalTva;

            return $carry;
        }, ['ht' => 0, 'tva' => 0, 'ttc' => 0]);

        return [
            'do_piece' => $data['do_piece'],
            'do_date' => $data['do_date'],
            'tier_id' => $data['tier_id'] ?? null,
            'depot_id' => $data['depot_id'] ?? null,
            'do_type' => $this->numericTypeFromCode($data['type_document_code']),
            'type_document_code' => $data['type_document_code'],
            'flux_type' => match ($definition['module'] ?? DocumentTypeRegistry::MODULE_SALES) {
                DocumentTypeRegistry::MODULE_PURCHASE => 'achat',
                DocumentTypeRegistry::MODULE_STOCK => 'stock',
                default => 'vente',
            },
            'doc_module' => $definition['module'] ?? DocumentTypeRegistry::MODULE_SALES,
            'workflow_type' => $definition['flow'] ?? 'order',
            'transporteur_id' => $data['transporteur_id'] ?? null,
            'do_lieu_livraison' => $data['do_lieu_livraison'] ?? null,
            'do_date_livraison' => $data['do_date_livraison'] ?? null,
            'do_expedition_statut' => $data['do_expedition_statut'],
            'do_total_ht' => $totals['ht'],
            'do_total_tva' => $totals['tva'],
            'do_total_ttc' => $totals['ttc'],
            'do_montant_regle' => 0,
        ];
    }

    public function syncLines(Document $document, array $lines): void
    {
        $taxRates = $this->articleTaxRates(collect($lines)->pluck('article_id')->all());

        foreach ($lines as $line) {
            $quantity = (float) $line['dl_qte'];
            $price = (float) $line['dl_prix_unitaire_ht'];
            $discount = $this->lineDiscount($line);
            $lineTotalHt = $this->lineTotalHt($line);
            $taxRate = (float) ($taxRates[(int) $line['article_id']] ?? 0);
            $lineTotalTtc = $lineTotalHt + ($lineTotalHt * ($taxRate / 100));

            $document->lines()->create([
                'article_id' => $line['article_id'],
                'dl_qte' => $quantity,
                'dl_prix_unitaire_ht' => $price,
                'dl_remise_percent' => $discount,
                'dl_montant_ht' => $lineTotalHt,
                'dl_montant_ttc' => $lineTotalTtc,
            ]);
        }
    }

    private function lineDiscount(array $line): float
    {
        return max(0, min(100, (float) ($line['dl_remise_percent'] ?? 0)));
    }

    private function lineTotalHt(array $line): float
    {
        $grossTotal = (float) $line['dl_qte'] * (float) $line['dl_prix_unitaire_ht'];
        $discount = $this->lineDiscount($line);

        return $grossTotal - ($grossTotal * ($discount / 100));
    }

    private function articleTaxRates(array $articleIds): array
    {
        return Article::query()
            ->whereIn('id', array_filter(array_map('intval', $articleIds)))
            ->pluck('ar_tva', 'id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function numericTypeFromCode(string $code): int
    {
        return match ($code) {
            'BL' => 2,
            'FA' => 3,
            'BR', 'FR' => 4,
            default => 1,
        };
    }
}
