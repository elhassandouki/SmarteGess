<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDepotsTable = Schema::hasTable('f_depots');

        Schema::table('f_docentete', function (Blueprint $table) use ($hasDepotsTable) {
            if (! Schema::hasColumn('f_docentete', 'depot_id')) {
                if ($hasDepotsTable) {
                    $table->foreignId('depot_id')->nullable()->after('tier_id')->constrained('f_depots')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('depot_id')->nullable()->after('tier_id');
                    $table->index('depot_id', 'idx_f_docentete_depot_id');
                }
            }

            if (! Schema::hasColumn('f_docentete', 'do_total_tva')) {
                $table->decimal('do_total_tva', 18, 2)->default(0)->after('do_total_ht');
            }

            if (! Schema::hasColumn('f_docentete', 'do_montant_regle')) {
                $table->decimal('do_montant_regle', 18, 2)->default(0)->after('do_total_ttc');
            }

            if (! Schema::hasColumn('f_docentete', 'do_statut')) {
                $table->tinyInteger('do_statut')->default(0)->after('do_montant_regle');
            }
        });
    }

    public function down(): void
    {
        $hasDepotsTable = Schema::hasTable('f_depots');

        Schema::table('f_docentete', function (Blueprint $table) use ($hasDepotsTable) {
            if (Schema::hasColumn('f_docentete', 'depot_id')) {
                if ($hasDepotsTable) {
                    $table->dropConstrainedForeignId('depot_id');
                } else {
                    $table->dropIndex('idx_f_docentete_depot_id');
                    $table->dropColumn('depot_id');
                }
            }
        });
    }
};
