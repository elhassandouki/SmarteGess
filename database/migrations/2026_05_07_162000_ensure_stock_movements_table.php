<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('article_id')->constrained('f_articles')->cascadeOnDelete();
                $table->foreignId('depot_id')->constrained('f_depots')->cascadeOnDelete();
                $table->enum('movement_type', ['IN', 'OUT', 'TRANSFER', 'ADJUSTMENT', 'RETURN']);
                $table->decimal('quantity', 18, 3);
                $table->string('reference_type')->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->string('reference')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['article_id', 'depot_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
