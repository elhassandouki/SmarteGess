<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convertBusinessTablesToInnoDb();

        Schema::create('f_comptet', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('ct_num')->unique()->index();
            $table->string('ct_intitule')->index();
            $table->enum('ct_type', ['client', 'fournisseur', 'prospect'])->index();
            $table->string('ct_ice', 15)->nullable()->unique();
            $table->string('ct_if', 20)->nullable();
            $table->decimal('ct_encours_max', 18, 2)->default(0);
            $table->integer('ct_delai_paiement')->default(0);
            $table->string('ct_telephone')->nullable();
            $table->string('ct_adresse')->nullable();
            $table->timestamps();
        });

        Schema::table('f_articles', function (Blueprint $table) {
            $table->string('ar_code_barre')->nullable()->unique()->after('ar_design');
            $table->decimal('ar_prix_revient', 18, 5)->default(0)->after('ar_prix_vente');
            $table->decimal('ar_tva', 5, 2)->default(20.00)->after('ar_prix_revient');
            $table->decimal('ar_stock_min', 18, 3)->default(0)->after('ar_unite');
            $table->boolean('ar_suivi_stock')->default(true)->after('ar_stock_actuel');
            $table->softDeletes();
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->decimal('do_total_tva', 18, 2)->default(0)->after('do_total_ht');
            $table->decimal('do_montant_regle', 18, 2)->default(0)->after('do_total_ttc');
            $table->tinyInteger('do_statut')->default(0)->after('do_montant_regle');
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->foreign('tier_id')->references('id')->on('f_comptet')->nullOnDelete();
        });

        Schema::table('f_docligne', function (Blueprint $table) {
            $table->decimal('dl_prix_revient', 18, 5)->default(0)->after('dl_prix_unitaire_ht');
            $table->decimal('dl_remise_percent', 5, 2)->default(0)->after('dl_prix_revient');
            $table->decimal('dl_montant_ht', 18, 2)->default(0)->after('dl_remise_percent');
        });

        Schema::create('f_reglements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('doc_id')->nullable()->constrained('f_docentete')->nullOnDelete();
            $table->foreignId('tier_id')->constrained('f_comptet');
            $table->date('rg_date')->index();
            $table->string('rg_libelle')->nullable();
            $table->decimal('rg_montant', 18, 2);
            $table->tinyInteger('rg_mode_reglement')->default(1);
            $table->string('rg_reference')->nullable();
            $table->date('rg_date_echeance')->nullable();
            $table->string('rg_banque')->nullable();
            $table->boolean('rg_valide')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_reglements');

        Schema::table('f_docligne', function (Blueprint $table) {
            $table->dropColumn(['dl_prix_revient', 'dl_remise_percent', 'dl_montant_ht']);
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->dropForeign(['tier_id']);
            $table->dropColumn(['do_total_tva', 'do_montant_regle', 'do_statut']);
        });

        Schema::table('f_articles', function (Blueprint $table) {
            $table->dropUnique(['ar_code_barre']);
            $table->dropColumn([
                'ar_code_barre',
                'ar_prix_revient',
                'ar_tva',
                'ar_stock_min',
                'ar_suivi_stock',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('f_comptet');
    }

    protected function convertBusinessTablesToInnoDb(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['f_familles', 'f_articles', 'f_transporteurs', 'f_docentete', 'f_docligne'] as $table) {
            DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
        }
    }
};
