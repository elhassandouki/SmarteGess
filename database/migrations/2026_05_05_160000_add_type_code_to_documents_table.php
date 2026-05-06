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
            if (! Schema::hasColumn('f_docentete', 'type_document_code')) {
                $table->string('type_document_code', 10)->nullable()->after('do_type');
            }
        });

        DB::table('f_docentete')
            ->select('id', 'do_type', 'do_piece')
            ->orderBy('id')
            ->chunkById(500, function ($documents): void {
                foreach ($documents as $document) {
                    $piece = strtoupper((string) $document->do_piece);
                    $code = match (true) {
                        str_contains($piece, 'DEV') => 'DE',
                        str_contains($piece, 'BC') => 'BC',
                        str_contains($piece, 'BL') => 'BL',
                        str_contains($piece, 'BR') => 'BR',
                        str_contains($piece, 'FR') => 'FR',
                        str_contains($piece, 'FA') => 'FA',
                        default => match ((int) $document->do_type) {
                            2 => 'BL',
                            3 => 'FA',
                            4 => 'BR',
                            default => 'BC',
                        },
                    };

                    DB::table('f_docentete')
                        ->where('id', $document->id)
                        ->update(['type_document_code' => $code]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (Schema::hasColumn('f_docentete', 'type_document_code')) {
                $table->dropColumn('type_document_code');
            }
        });
    }
};
