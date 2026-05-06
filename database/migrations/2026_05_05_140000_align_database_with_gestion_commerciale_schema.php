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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('USER')->after('password');
            }
        });

        Schema::table('f_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('f_articles', 'code_article')) {
                $table->string('code_article')->nullable()->after('ar_ref');
            }
        });

        Schema::table('f_comptet', function (Blueprint $table) {
            if (! Schema::hasColumn('f_comptet', 'code_tiers')) {
                $table->string('code_tiers')->nullable()->after('ct_num');
            }
        });

        DB::table('f_articles')
            ->whereNull('code_article')
            ->update(['code_article' => DB::raw('ar_ref')]);

        DB::table('f_comptet')
            ->whereNull('code_tiers')
            ->update(['code_tiers' => DB::raw('ct_num')]);

        Schema::create('f_taxes', function (Blueprint $table) {
            $table->id();
            $table->string('code_taxe', 20)->unique();
            $table->string('libelle', 100);
            $table->decimal('taux', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('f_depots', function (Blueprint $table) {
            $table->id();
            $table->string('code_depot', 50)->unique();
            $table->string('intitule');
            $table->timestamps();
        });

        Schema::create('f_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('f_articles')->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('f_depots')->cascadeOnDelete();
            $table->decimal('stock_reel', 18, 3)->default(0);
            $table->decimal('stock_reserve', 18, 3)->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'depot_id']);
        });

        Schema::create('compta_ecritures', function (Blueprint $table) {
            $table->id();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->date('date_ecriture')->nullable();
            $table->string('piece', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->nullable();
            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->index('do_type', 'idx_doc_type');
            $table->index('do_date', 'idx_doc_date');
        });

        Schema::table('f_articles', function (Blueprint $table) {
            $table->unique('code_article');
            $table->index('ar_ref', 'idx_article_code');
        });

        Schema::table('f_comptet', function (Blueprint $table) {
            $table->unique('code_tiers');
            $table->index('ct_num', 'idx_tiers_code');
        });

        Schema::table('f_stock', function (Blueprint $table) {
            $table->index('article_id', 'idx_stock_article');
            $table->index('depot_id', 'idx_stock_depot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('f_stock', function (Blueprint $table) {
            $table->dropIndex('idx_stock_article');
            $table->dropIndex('idx_stock_depot');
        });

        Schema::table('f_comptet', function (Blueprint $table) {
            $table->dropIndex('idx_tiers_code');
            $table->dropUnique(['code_tiers']);
            $table->dropColumn('code_tiers');
        });

        Schema::table('f_articles', function (Blueprint $table) {
            $table->dropIndex('idx_article_code');
            $table->dropUnique(['code_article']);
            $table->dropColumn('code_article');
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->dropIndex('idx_doc_type');
            $table->dropIndex('idx_doc_date');
        });

        Schema::dropIfExists('logs');
        Schema::dropIfExists('compta_ecritures');
        Schema::dropIfExists('f_stock');
        Schema::dropIfExists('f_depots');
        Schema::dropIfExists('f_taxes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
