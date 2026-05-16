<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Article;
use App\Models\CompteT;
use App\Models\Depot;
use App\Models\Document;
use App\Models\Stock;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_movement_throws_when_stock_would_go_negative(): void
    {
        $depot = Depot::create(['code_depot' => 'D2', 'intitule' => 'Depot 2']);
        $article = Article::create([
            'ar_ref' => 'ART-STK',
            'ar_design' => 'Article Stock',
            'ar_prix_achat' => 10,
            'ar_prix_vente' => 12,
            'ar_prix_revient' => 10,
            'ar_tva' => 20,
            'ar_stock_min' => 0,
            'ar_stock_actuel' => 0,
            'ar_suivi_stock' => true,
            'ar_unite' => 'Pcs',
        ]);
        $tier = CompteT::create(['ct_num' => 'C-STK', 'ct_intitule' => 'Client STK', 'ct_type' => 'client']);
        Stock::create(['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 1, 'stock_reserve' => 0]);

        $doc = Document::create([
            'do_piece' => 'BL-STK-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'depot_id' => $depot->id,
            'do_type' => 2,
            'type_document_code' => 'BL',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'delivery',
            'lifecycle_status' => 'validated',
            'do_expedition_statut' => 'en_attente',
            'do_total_ht' => 20,
            'do_total_tva' => 4,
            'do_total_ttc' => 24,
            'do_montant_regle' => 0,
            'do_statut' => 0,
        ]);
        $doc->lines()->create([
            'article_id' => $article->id,
            'dl_qte' => 2,
            'dl_prix_unitaire_ht' => 10,
            'dl_remise_percent' => 0,
            'dl_montant_ht' => 20,
            'dl_montant_ttc' => 24,
        ]);

        $this->expectException(InsufficientStockException::class);
        app(StockMovementService::class)->processDocumentMovement($doc->fresh('lines'));
    }
}

