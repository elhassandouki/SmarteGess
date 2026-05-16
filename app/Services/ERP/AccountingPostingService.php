<?php

namespace App\Services\ERP;

use App\Models\Document;
use App\Models\Reglement;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    public function clearDocumentPosting(int $documentId): void
    {
        DB::table('journal_entries')
            ->where('reference_type', 'document')
            ->where('reference_id', $documentId)
            ->delete();
    }

    public function clearPaymentPosting(int $paymentId): void
    {
        DB::table('journal_entries')
            ->where('reference_type', 'payment')
            ->where('reference_id', $paymentId)
            ->delete();
    }

    public function syncDocumentPosting(Document $document): void
    {
        $this->clearDocumentPosting($document->id);

        $code = (string) $document->type_document_code;

        if (!in_array($code, ['FA', 'FF'], true)) {
            return;
        }

        $isSalesInvoice = $code === 'FA';
        $entryId = DB::table('journal_entries')->insertGetId([
            'entry_date' => $document->do_date,
            'journal_code' => $isSalesInvoice ? 'SALES' : 'PURCHASE',
            'reference_type' => 'document',
            'reference_id' => $document->id,
            'reference_number' => $document->do_piece,
            'label' => $isSalesInvoice ? 'Facture client' : 'Facture fournisseur',
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($isSalesInvoice) {
            $this->insertLine($entryId, '411000', 'Client', (float) $document->do_total_ttc, 0);
            $this->insertLine($entryId, '707000', 'Ventes', 0, (float) $document->do_total_ht);
            if ((float) $document->do_total_tva > 0) {
                $this->insertLine($entryId, '445700', 'TVA collectee', 0, (float) $document->do_total_tva);
            }

            return;
        }

        $this->insertLine($entryId, '607000', 'Achats', (float) $document->do_total_ht, 0);
        if ((float) $document->do_total_tva > 0) {
            $this->insertLine($entryId, '445660', 'TVA deduct.', (float) $document->do_total_tva, 0);
        }
        $this->insertLine($entryId, '401000', 'Fournisseur', 0, (float) $document->do_total_ttc);
    }

    public function syncPaymentPosting(Reglement $reglement): void
    {
        $this->clearPaymentPosting($reglement->id);

        if (!$reglement->rg_valide) {
            return;
        }

        $docType = $reglement->document?->type_document_code;
        $isCustomerPayment = $docType === 'FA';

        $entryId = DB::table('journal_entries')->insertGetId([
            'entry_date' => $reglement->rg_date,
            'journal_code' => 'BANK',
            'reference_type' => 'payment',
            'reference_id' => $reglement->id,
            'reference_number' => $reglement->rg_reference,
            'label' => 'Reglement '.($reglement->document?->do_piece ?? ''),
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($isCustomerPayment) {
            $this->insertLine($entryId, '512000', 'Banque/Caisse', (float) $reglement->rg_montant, 0);
            $this->insertLine($entryId, '411000', 'Client', 0, (float) $reglement->rg_montant);
            return;
        }

        $this->insertLine($entryId, '401000', 'Fournisseur', (float) $reglement->rg_montant, 0);
        $this->insertLine($entryId, '512000', 'Banque/Caisse', 0, (float) $reglement->rg_montant);
    }

    private function insertLine(int $entryId, string $accountCode, string $accountLabel, float $debit, float $credit): void
    {
        DB::table('journal_entry_lines')->insert([
            'journal_entry_id' => $entryId,
            'account_code' => $accountCode,
            'account_label' => $accountLabel,
            'debit' => $debit,
            'credit' => $credit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
