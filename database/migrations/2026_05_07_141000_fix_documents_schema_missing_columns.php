<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (! Schema::hasColumn('f_docentete', 'depot_id')) {
                $table->foreignId('depot_id')->nullable()->after('tier_id')->constrained('f_depots')->nullOnDelete();
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
        Schema::table('f_docentete', function (Blueprint $table) {
            if (Schema::hasColumn('f_docentete', 'depot_id')) {
                $table->dropConstrainedForeignId('depot_id');
            }
        });
    }
};
