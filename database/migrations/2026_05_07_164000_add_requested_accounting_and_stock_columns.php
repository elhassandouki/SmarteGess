<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_accounts')) {
            Schema::create('accounting_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('entry_lines')) {
            Schema::create('entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->string('account_code', 20)->index();
                $table->string('account_label');
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_movements', 'type')) {
                    $table->enum('type', ['entree', 'sortie', 'transfer', 'adjustment'])->nullable()->after('movement_type');
                }
                if (!Schema::hasColumn('stock_movements', 'source_type')) {
                    $table->string('source_type')->nullable()->after('type');
                    $table->index('source_type', 'idx_stock_movements_source_type');
                }
                if (!Schema::hasColumn('stock_movements', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                    $table->index('source_id', 'idx_stock_movements_source_id');
                }
            });

            DB::table('stock_movements')->orderBy('id')->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $type = match ((string) $row->movement_type) {
                        'IN', 'RETURN' => 'entree',
                        'OUT' => 'sortie',
                        'TRANSFER' => 'transfer',
                        default => 'adjustment',
                    };

                    DB::table('stock_movements')->where('id', $row->id)->update([
                        'type' => $type,
                        'source_type' => $row->reference_type,
                        'source_id' => $row->reference_id,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (Schema::hasColumn('stock_movements', 'source_id')) {
                    $table->dropIndex('idx_stock_movements_source_id');
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('stock_movements', 'source_type')) {
                    $table->dropIndex('idx_stock_movements_source_type');
                    $table->dropColumn('source_type');
                }
                if (Schema::hasColumn('stock_movements', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }

        Schema::dropIfExists('entry_lines');
        Schema::dropIfExists('accounting_accounts');
    }
};
