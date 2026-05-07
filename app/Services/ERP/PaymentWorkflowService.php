<?php

namespace App\Services\ERP;

use App\Models\Document;

class PaymentWorkflowService
{
    public function syncDocumentPayment(?Document $document): void
    {
        if (! $document) {
            return;
        }

        $paid = (float) $document->reglements()->where('rg_valide', true)->sum('rg_montant');
        $total = (float) $document->do_total_ttc;

        $document->update([
            'do_montant_regle' => $paid,
            'do_statut' => $paid >= $total && $total > 0 ? 2 : ($paid > 0 ? 1 : 0),
        ]);
    }
}
