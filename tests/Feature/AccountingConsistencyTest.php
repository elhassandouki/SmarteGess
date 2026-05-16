<?php

namespace Tests\Feature;

use App\Models\CompteT;
use App\Models\Document;
use App\Services\ERP\AccountingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_writes_to_single_accounting_lines_table(): void
    {
        $tier = CompteT::create(['ct_num' => 'C-ACC', 'ct_intitule' => 'Client ACC', 'ct_type' => 'client']);

        $doc = Document::create([
            'do_piece' => 'FA-ACC-001',
            'do_date' => now()->toDateString(),
            'tier_id' => $tier->id,
            'do_type' => 3,
            'type_document_code' => 'FA',
            'flux_type' => 'vente',
            'doc_module' => 'sales',
            'workflow_type' => 'invoice',
            'lifecycle_status' => 'posted',
            'do_expedition_statut' => 'en_attente',
            'do_total_ht' => 100,
            'do_total_tva' => 20,
            'do_total_ttc' => 120,
            'do_montant_regle' => 0,
            'do_statut' => 0,
        ]);

        app(AccountingPostingService::class)->syncDocumentPosting($doc);

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_entry_lines', 3);
        $this->assertFalse(Schema::hasTable('entry_lines'));
    }
}

