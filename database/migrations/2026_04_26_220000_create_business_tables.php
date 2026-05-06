<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('f_familles', function (Blueprint $table) {
            $table->id();
            $table->string('fa_code')->unique();
            $table->string('fa_intitule');
            $table->timestamps();
        });

        Schema::create('f_articles', function (Blueprint $table) {
            $table->id();
            $table->string('ar_ref')->unique()->index();
            $table->string('ar_design')->index();
            $table->foreignId('family_id')
                ->nullable()
                ->constrained('f_familles')
                ->nullOnDelete();
            $table->decimal('ar_prix_achat', 18, 5)->default(0);
            $table->decimal('ar_prix_vente', 18, 5)->default(0);
            $table->decimal('ar_stock_actuel', 18, 3)->default(0);
            $table->string('ar_unite')->default('Pcs');
            $table->timestamps();
        });

        Schema::create('f_transporteurs', function (Blueprint $table) {
            $table->id();
            $table->string('tr_nom');
            $table->string('tr_matricule')->nullable();
            $table->string('tr_chauffeur')->nullable();
            $table->string('tr_telephone')->nullable();
            $table->timestamps();
        });

        Schema::create('f_docentete', function (Blueprint $table) {
            $table->id();
            $table->string('do_piece')->unique()->index();
            $table->date('do_date')->index();
            $table->unsignedBigInteger('tier_id')->nullable()->index()->comment('Reference to f_comptet.id when the table is added');
            $table->tinyInteger('do_type')->index();
            $table->foreignId('transporteur_id')
                ->nullable()
                ->constrained('f_transporteurs')
                ->nullOnDelete();
            $table->string('do_lieu_livraison')->nullable();
            $table->date('do_date_livraison')->nullable();
            $table->enum('do_expedition_statut', ['en_attente', 'en_cours', 'livre'])->default('en_attente');
            $table->decimal('do_total_ht', 18, 2)->default(0);
            $table->decimal('do_total_ttc', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('f_docligne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_id')
                ->constrained('f_docentete')
                ->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('f_articles');
            $table->decimal('dl_qte', 18, 3);
            $table->decimal('dl_prix_unitaire_ht', 18, 5);
            $table->decimal('dl_montant_ttc', 18, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_docligne');
        Schema::dropIfExists('f_docentete');
        Schema::dropIfExists('f_transporteurs');
        Schema::dropIfExists('f_articles');
        Schema::dropIfExists('f_familles');
    }
};
