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
            if (!Schema::hasColumn('f_docentete', 'lifecycle_status')) {
                $table->enum('lifecycle_status', ['draft', 'validated', 'posted', 'cancelled'])
                    ->default('draft')
                    ->after('workflow_type')
                    ->index('idx_doc_lifecycle_status');
            }
            if (!Schema::hasColumn('f_docentete', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('lifecycle_status');
            }
            if (!Schema::hasColumn('f_docentete', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('posted_at');
            }
        });

        // Migrate orphan duplicate accounting rows if any.
        if (Schema::hasTable('entry_lines') && Schema::hasTable('journal_entry_lines')) {
            $rows = DB::table('entry_lines')->get();
            foreach ($rows as $row) {
                DB::table('journal_entry_lines')->insert([
                    'journal_entry_id' => $row->journal_entry_id,
                    'account_code' => $row->account_code,
                    'account_label' => $row->account_label,
                    'debit' => $row->debit,
                    'credit' => $row->credit,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
            Schema::drop('entry_lines');
        }

        if (Schema::hasTable('accounting_accounts')) {
            Schema::drop('accounting_accounts');
        }
    }

    public function down(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (Schema::hasColumn('f_docentete', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('f_docentete', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
            if (Schema::hasColumn('f_docentete', 'lifecycle_status')) {
                $table->dropIndex('idx_doc_lifecycle_status');
                $table->dropColumn('lifecycle_status');
            }
        });
    }
};

