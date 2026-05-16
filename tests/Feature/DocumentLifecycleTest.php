<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CompteT;
use App\Models\Depot;
use App\Models\Document;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_and_accounting_are_applied_only_when_document_is_posted(): void
    {
        $user = User::factory()->create();
        foreach (['documents.create', 'documents.view', 'documents.status', 'documents.delete'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user->givePermissionTo(['documents.create', 'documents.view', 'documents.status', 'documents.delete']);

        $depot = Depot::create(['code_depot' => 'D1', 'intitule' => 'Depot 1']);
        $article = Article::create([
            'ar_ref' => 'ART-LC',
            'ar_design' => 'Article lifecycle',
            'ar_prix_achat' => 20,
            'ar_prix_vente' => 30,
            'ar_prix_revient' => 20,
            'ar_tva' => 20,
            'ar_stock_min' => 0,
            'ar_stock_actuel' => 0,
            'ar_suivi_stock' => true,
            'ar_unite' => 'Pcs',
        ]);
        $tier = CompteT::create(['ct_num' => 'C-1', 'ct_intitule' => 'Client', 'ct_type' => 'client']);
        Stock::create(['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 10, 'stock_reserve' => 0]);

        $this->actingAs($user)->post(route('documents.store'), [
            'do_piece' => 'BL-LC-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'depot_id' => $depot->id,
            'type_document_code' => 'BL',
            'do_expedition_statut' => 'en_attente',
            'lines' => [[
                'article_id' => $article->id,
                'dl_qte' => 2,
                'dl_prix_unitaire_ht' => 30,
                'dl_remise_percent' => 0,
            ]],
        ])->assertRedirect(route('documents.index'));

        $doc = Document::where('do_piece', 'BL-LC-001')->firstOrFail();
        $this->assertSame('draft', $doc->lifecycle_status);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('journal_entries', 0);

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertRedirect(route('documents.show', $doc));
        $doc->refresh();
        $this->assertSame('posted', $doc->lifecycle_status);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('f_stock', ['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 8.000]);

        $this->actingAs($user)->patch(route('documents.cancel', $doc))->assertRedirect(route('documents.show', $doc));
        $doc->refresh();
        $this->assertSame('cancelled', $doc->lifecycle_status);
        $this->assertDatabaseHas('f_stock', ['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 10.000]);
    }
}

