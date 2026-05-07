<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f_comptet', function (Blueprint $table) {
            if (!Schema::hasColumn('f_comptet', 'entity_type')) {
                $table->enum('entity_type', ['client', 'supplier', 'prospect'])->default('client')->after('ct_type');
                $table->index('entity_type', 'idx_f_comptet_entity_type');
            }
        });

        DB::table('f_comptet')->select('id', 'ct_type')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $map = match ((string) $row->ct_type) {
                    'fournisseur' => 'supplier',
                    'prospect' => 'prospect',
                    default => 'client',
                };

                DB::table('f_comptet')->where('id', $row->id)->update(['entity_type' => $map]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('f_comptet', function (Blueprint $table) {
            if (Schema::hasColumn('f_comptet', 'entity_type')) {
                $table->dropIndex('idx_f_comptet_entity_type');
                $table->dropColumn('entity_type');
            }
        });
    }
};
