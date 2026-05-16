<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_article_with_business_fields(): void
    {
        $user = User::factory()->create(['role' => 'COMMERCIAL']);

        $this->actingAs($user)->post(route('articles.store'), [
            'ar_ref' => 'ART-100',
            'ar_design' => 'Article de test',
            'ar_code_barre' => '123456789',
            'ar_prix_achat' => 15,
            'ar_prix_vente' => 22.5,
            'ar_prix_revient' => 16.25,
            'ar_tva' => 20,
            'ar_stock_min' => 3,
            'ar_stock_actuel' => 7,
            'ar_suivi_stock' => 1,
            'ar_unite' => 'Pcs',
        ])->assertRedirect(route('articles.index'));

        $this->assertDatabaseHas('f_articles', [
            'ar_ref' => 'ART-100',
            'ar_code_barre' => '123456789',
            'ar_tva' => 20.00,
            'ar_stock_min' => 3.000,
            'ar_suivi_stock' => 1,
        ]);
    }

    public function test_document_creation_computes_totals_from_lines_and_taxes(): void
    {
        $user = User::factory()->create(['role' => 'COMMERCIAL']);
        $article = Article::create([
            'ar_ref' => 'ART-200',
            'ar_design' => 'Article facture',
            'ar_prix_achat' => 50,
            'ar_prix_vente' => 100,
            'ar_prix_revient' => 50,
            'ar_tva' => 20,
            'ar_stock_min' => 1,
            'ar_stock_actuel' => 10,
            'ar_suivi_stock' => true,
            'ar_unite' => 'Pcs',
        ]);
        $tier = CompteT::create([
            'ct_num' => 'CLI-001',
            'ct_intitule' => 'Client test',
            'ct_type' => 'client',
        ]);

        $this->actingAs($user)->post(route('documents.store'), [
            'do_piece' => 'FAC-20260505',
            'do_date' => '2026-05-05',
            'tier_id' => $tier->id,
            'do_type' => 3,
            'do_expedition_statut' => 'en_attente',
            'lines' => [
                [
                    'article_id' => $article->id,
                    'dl_qte' => 2,
                    'dl_prix_unitaire_ht' => 100,
                    'dl_remise_percent' => 10,
                ],
            ],
        ])->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('f_docentete', [
            'do_piece' => 'FAC-20260505',
            'do_total_ht' => 180.00,
            'do_total_tva' => 36.00,
            'do_total_ttc' => 216.00,
        ]);

        $this->assertDatabaseHas('f_docligne', [
            'article_id' => $article->id,
            'dl_montant_ht' => 180.00,
            'dl_montant_ttc' => 216.00,
        ]);
    }
}
