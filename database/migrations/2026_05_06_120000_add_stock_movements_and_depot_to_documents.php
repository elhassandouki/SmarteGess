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
        // Create stock movements table for audit trail
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('f_articles')->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('f_depots')->cascadeOnDelete();
            $table->enum('movement_type', ['IN', 'OUT', 'ADJUSTMENT', 'RETURN'])->comment('IN=purchase/receipt, OUT=sale/delivery, ADJUSTMENT=manual, RETURN=return');
            $table->decimal('quantity', 18, 3);
            $table->string('reference')->nullable()->comment('Document reference');
            $table->string('reference_type')->nullable()->comment('Document type: BC, BL, FA, BR, FR');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Document ID');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('article_id');
            $table->index('depot_id');
            $table->index('reference_type');
            $table->index('created_at');
        });

        // Add type_document_code to documents if not already there
        Schema::table('f_docentete', function (Blueprint $table) {
            if (! Schema::hasColumn('f_docentete', 'type_document_code')) {
                $table->string('type_document_code', 2)->nullable()->after('do_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            $table->dropColumn('type_document_code');
        });

        Schema::dropIfExists('stock_movements');

        Schema::table('f_docentete', function (Blueprint $table) {
            $table->dropForeignKeyConstraints();
            $table->dropColumn('depot_id');
        });
    }
};
