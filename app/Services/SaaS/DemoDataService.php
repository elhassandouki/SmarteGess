<?php

namespace App\Services\SaaS;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\Document;
use App\Models\DocumentLine;

class DemoDataService
{
    public function seedForTenant(int $tenantId): void
    {
        $client = CompteT::create([
            'ct_num' => 'CL-DEMO-001',
            'code_tiers' => 'CL-DEMO-001',
            'ct_intitule' => 'Client Demo',
            'ct_type' => 'client',
            'entity_type' => 'client',
        ]);

        $article = Article::create([
            'ar_ref' => 'ART-DEMO-001',
            'code_article' => 'ART-DEMO-001',
            'ar_design' => 'Produit Demo',
            'ar_prix_achat' => 50,
            'ar_prix_vente' => 80,
            'ar_prix_revient' => 50,
            'ar_tva' => 20,
            'ar_stock_min' => 5,
            'ar_stock_actuel' => 50,
            'ar_suivi_stock' => true,
            'ar_unite' => 'pcs',
        ]);

        $doc = Document::create([
            'tenant_id' => $tenantId,
            'do_piece' => 'FAC-DEMO-0001',
            'do_date' => now()->toDateString(),
            'tier_id' => $client->id,
            'do_type' => 3,
            'type_document_code' => 'FA',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'invoice',
            'lifecycle_status' => 'draft',
            'do_expedition_statut' => 'en_attente',
            'do_total_ht' => 80,
            'do_total_tva' => 16,
            'do_total_ttc' => 96,
            'do_montant_regle' => 0,
            'do_statut' => 0,
        ]);

        DocumentLine::create([
            'doc_id' => $doc->id,
            'article_id' => $article->id,
            'dl_qte' => 1,
            'dl_prix_unitaire_ht' => 80,
            'dl_remise_percent' => 0,
            'dl_montant_ht' => 80,
            'dl_montant_ttc' => 96,
        ]);
    }
}
