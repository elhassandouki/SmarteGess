<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_code', 20)->unique();
                $table->string('account_label');
                $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->date('entry_date')->index();
                $table->string('journal_code', 20)->index();
                $table->string('reference_type', 30)->nullable()->index();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->string('reference_number')->nullable();
                $table->string('label');
                $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->string('account_code', 20)->index();
                $table->string('account_label');
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('chart_of_accounts') && DB::table('chart_of_accounts')->count() === 0) {
            DB::table('chart_of_accounts')->insert([
                ['account_code' => '411000', 'account_label' => 'Clients', 'account_type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '401000', 'account_label' => 'Fournisseurs', 'account_type' => 'liability', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '707000', 'account_label' => 'Ventes de marchandises', 'account_type' => 'revenue', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '607000', 'account_label' => 'Achats de marchandises', 'account_type' => 'expense', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '445700', 'account_label' => 'TVA collectee', 'account_type' => 'liability', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '445660', 'account_label' => 'TVA deductible', 'account_type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['account_code' => '512000', 'account_label' => 'Banque/Caisse', 'account_type' => 'asset', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
