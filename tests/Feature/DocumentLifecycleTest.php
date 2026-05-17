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
        foreach (['documents.create', 'documents.view', 'documents.update', 'documents.status', 'documents.delete'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user->givePermissionTo(['documents.create', 'documents.view', 'documents.update', 'documents.status', 'documents.delete']);

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

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertStatus(500);
        $doc->refresh();
        $this->assertSame('draft', $doc->lifecycle_status);

        $this->actingAs($user)->patch(route('documents.validate', $doc))->assertRedirect(route('documents.show', $doc));
        $doc->refresh();
        $this->assertSame('validated', $doc->lifecycle_status);

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertRedirect(route('documents.show', $doc));
        $doc->refresh();
        $this->assertSame('posted', $doc->lifecycle_status);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseHas('f_stock', ['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 8.000]);

        $this->actingAs($user)->patch(route('documents.cancel', $doc))->assertRedirect(route('documents.show', $doc));
        $doc->refresh();
        $this->assertSame('cancelled', $doc->lifecycle_status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('f_stock', ['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 10.000]);
    }

    public function test_cancelled_invoice_reverses_accounting_posting(): void
    {
        $user = User::factory()->create();
        foreach (['documents.view', 'documents.update', 'documents.status', 'documents.delete'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user->givePermissionTo(['documents.view', 'documents.update', 'documents.status', 'documents.delete']);

        $tier = CompteT::create(['ct_num' => 'C-INV', 'ct_intitule' => 'Client INV', 'ct_type' => 'client']);

        $doc = Document::create([
            'do_piece' => 'FA-LC-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'do_type' => 3,
            'type_document_code' => 'FA',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'invoice',
            'lifecycle_status' => 'validated',
            'do_expedition_statut' => 'en_attente',
            'do_total_ht' => 100,
            'do_total_tva' => 20,
            'do_total_ttc' => 120,
            'do_montant_regle' => 0,
            'do_statut' => 0,
        ]);

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertRedirect(route('documents.show', $doc));
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_entry_lines', 3);

        $this->actingAs($user)->patch(route('documents.cancel', $doc))->assertRedirect(route('documents.show', $doc));
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_entry_lines', 0);
    }

    public function test_posting_is_idempotent_and_cannot_run_twice(): void
    {
        $user = User::factory()->create();
        foreach (['documents.view', 'documents.update', 'documents.status'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user->givePermissionTo(['documents.view', 'documents.update', 'documents.status']);

        $tier = CompteT::create(['ct_num' => 'C-IDEMP', 'ct_intitule' => 'Client IDEMP', 'ct_type' => 'client']);
        $doc = Document::create([
            'do_piece' => 'FA-IDEMP-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'do_type' => 3,
            'type_document_code' => 'FA',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'invoice',
            'lifecycle_status' => 'validated',
            'do_expedition_statut' => 'en_attente',
            'do_total_ht' => 200,
            'do_total_tva' => 40,
            'do_total_ttc' => 240,
            'do_montant_regle' => 0,
            'do_statut' => 0,
        ]);

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertRedirect(route('documents.show', $doc));
        $this->assertDatabaseCount('journal_entries', 1);

        $this->actingAs($user)->patch(route('documents.post', $doc))->assertStatus(500);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_document_duplication_stays_draft_and_does_not_post_stock_or_accounting(): void
    {
        $user = User::factory()->create();
        foreach (['documents.create', 'documents.view', 'documents.status', 'documents.duplicate'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user->givePermissionTo(['documents.create', 'documents.view', 'documents.status', 'documents.duplicate']);

        $depot = Depot::create(['code_depot' => 'D1', 'intitule' => 'Depot 1']);
        $article = Article::create([
            'ar_ref' => 'ART-DUP',
            'ar_design' => 'Article duplication',
            'ar_prix_achat' => 20,
            'ar_prix_vente' => 30,
            'ar_prix_revient' => 20,
            'ar_tva' => 20,
            'ar_stock_min' => 0,
            'ar_stock_actuel' => 0,
            'ar_suivi_stock' => true,
            'ar_unite' => 'Pcs',
        ]);
        $tier = CompteT::create(['ct_num' => 'C-2', 'ct_intitule' => 'Client 2', 'ct_type' => 'client']);
        Stock::create(['article_id' => $article->id, 'depot_id' => $depot->id, 'stock_reel' => 10, 'stock_reserve' => 0]);

        $doc = Document::create([
            'do_piece' => 'BL-DUP-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'depot_id' => $depot->id,
            'do_type' => 2,
            'type_document_code' => 'BL',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'delivery',
            'lifecycle_status' => 'posted',
            'posted_at' => now(),
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

        $this->actingAs($user)->post(route('documents.duplicate', $doc))->assertRedirect();

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('journal_entries', 0);

        $copy = Document::query()->where('id', '!=', $doc->id)->latest('id')->firstOrFail();
        $this->assertSame('draft', $copy->lifecycle_status);
        $this->assertNull($copy->posted_at);
        $this->assertNull($copy->cancelled_at);
    }
}
