<?php

namespace App\Services\ERP;

use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\CompanySetting;
use App\Services\SaaS\InvoiceNumberingService;
use Illuminate\Support\Facades\DB;

class DocumentManagementService
{
    public function __construct(
        private readonly DocumentWorkflowService $workflowService,
        private readonly InvoiceNumberingService $invoiceNumberingService
    ) {
    }

    public function create(array $data): Document
    {
        return DB::transaction(function () use ($data): Document {
            if (empty($data['do_piece'])) {
                $tenantId = (int) (auth()->user()?->tenant_id ?? 0);
                $prefix = CompanySetting::query()->where('tenant_id', $tenantId)->value('invoice_prefix') ?: 'DOC';
                $data['do_piece'] = $this->invoiceNumberingService->nextNumber(
                    tenantId: $tenantId,
                    documentCode: (string) $data['type_document_code'],
                    prefix: $prefix
                );
            }

            $document = Document::create($this->workflowService->buildHeaderData($data));
            $this->workflowService->syncLines($document, $data['lines']);

            return $document;
        });
    }

    public function update(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data): Document {
            if ($document->lifecycle_status === 'posted') {
                throw new \RuntimeException('Posted documents cannot be edited directly. Cancel first.');
            }

            $headerData = $this->workflowService->buildHeaderData($data, $document->lifecycle_status);
            $document->update($headerData);
            $document->lines()->delete();
            $this->workflowService->syncLines($document, $data['lines']);

            return $document->fresh('lines');
        });
    }

    public function duplicate(Document $document): Document
    {
        $document->loadMissing('lines');

        return DB::transaction(function () use ($document): Document {
            $copy = Document::create([
                'do_piece' => $this->generateDuplicatePiece($document->do_piece),
                'do_date' => now()->toDateString(),
                'tier_id' => $document->tier_id,
                'do_type' => $document->do_type,
                'type_document_code' => $document->type_document_code,
                'flux_type' => $document->flux_type,
                'doc_module' => $document->doc_module,
                'workflow_type' => $document->workflow_type,
                'lifecycle_status' => 'draft',
                'posted_at' => null,
                'cancelled_at' => null,
                'depot_id' => $document->depot_id,
                'transporteur_id' => $document->transporteur_id,
                'do_lieu_livraison' => $document->do_lieu_livraison,
                'do_date_livraison' => $document->do_date_livraison,
                'do_expedition_statut' => 'en_attente',
                'do_total_ht' => $document->do_total_ht,
                'do_total_tva' => $document->do_total_tva,
                'do_total_ttc' => $document->do_total_ttc,
                'do_montant_regle' => 0,
                'do_statut' => 0,
            ]);

            $document->lines->each(function (DocumentLine $line) use ($copy): void {
                $copy->lines()->create([
                    'article_id' => $line->article_id,
                    'dl_qte' => $line->dl_qte,
                    'dl_prix_unitaire_ht' => $line->dl_prix_unitaire_ht,
                    'dl_prix_revient' => $line->dl_prix_revient,
                    'dl_remise_percent' => $line->dl_remise_percent,
                    'dl_montant_ht' => $line->dl_montant_ht,
                    'dl_montant_ttc' => $line->dl_montant_ttc,
                ]);
            });

            return $copy;
        });
    }

    private function generateDuplicatePiece(string $piece): string
    {
        $basePiece = mb_substr($piece, 0, 80);
        $candidate = $basePiece.'-COPIE';
        $suffix = 2;

        while (Document::where('do_piece', $candidate)->exists()) {
            $candidate = $basePiece.'-COPIE-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
