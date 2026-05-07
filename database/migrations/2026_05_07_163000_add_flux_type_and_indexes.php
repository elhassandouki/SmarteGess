<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (!Schema::hasColumn('f_docentete', 'flux_type')) {
                $table->enum('flux_type', ['vente', 'achat', 'stock'])->nullable()->after('type_document_code');
                $table->index('flux_type', 'idx_doc_flux_type');
            }
        });

        if (Schema::hasColumn('f_docentete', 'flux_type')) {
            DB::table('f_docentete')->select('id', 'doc_module')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $flux = match ((string) $row->doc_module) {
                        'purchase' => 'achat',
                        'stock' => 'stock',
                        default => 'vente',
                    };
                    DB::table('f_docentete')->where('id', $row->id)->update(['flux_type' => $flux]);
                }
            });
        }

        Schema::table('f_docligne', function (Blueprint $table) {
            $table->index('doc_id', 'idx_f_docligne_doc_id');
            $table->index('article_id', 'idx_f_docligne_article_id');
        });

        Schema::table('f_reglements', function (Blueprint $table) {
            $table->index('doc_id', 'idx_f_reglements_doc_id');
            $table->index('tier_id', 'idx_f_reglements_tier_id');
        });
    }

    public function down(): void
    {
        Schema::table('f_reglements', function (Blueprint $table) {
            $table->dropIndex('idx_f_reglements_doc_id');
            $table->dropIndex('idx_f_reglements_tier_id');
        });

        Schema::table('f_docligne', function (Blueprint $table) {
            $table->dropIndex('idx_f_docligne_doc_id');
            $table->dropIndex('idx_f_docligne_article_id');
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            if (Schema::hasColumn('f_docentete', 'flux_type')) {
                $table->dropIndex('idx_doc_flux_type');
                $table->dropColumn('flux_type');
            }
        });
    }
};
