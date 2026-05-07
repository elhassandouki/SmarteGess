<?php

use App\Support\DocumentTypeRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (! Schema::hasColumn('f_docentete', 'doc_module')) {
                $table->string('doc_module', 20)->nullable()->after('type_document_code');
                $table->index('doc_module', 'idx_doc_module');
            }

            if (! Schema::hasColumn('f_docentete', 'workflow_type')) {
                $table->string('workflow_type', 30)->nullable()->after('doc_module');
                $table->index('workflow_type', 'idx_doc_workflow_type');
            }
        });

        $definitions = DocumentTypeRegistry::definitions();

        DB::table('f_docentete')
            ->select('id', 'type_document_code')
            ->orderBy('id')
            ->chunkById(500, function ($documents) use ($definitions): void {
                foreach ($documents as $document) {
                    $code = (string) $document->type_document_code;
                    $meta = $definitions[$code] ?? ['module' => DocumentTypeRegistry::MODULE_SALES, 'flow' => 'order'];

                    DB::table('f_docentete')
                        ->where('id', $document->id)
                        ->update([
                            'doc_module' => $meta['module'],
                            'workflow_type' => $meta['flow'],
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('f_docentete', function (Blueprint $table) {
            if (Schema::hasColumn('f_docentete', 'workflow_type')) {
                $table->dropIndex('idx_doc_workflow_type');
                $table->dropColumn('workflow_type');
            }

            if (Schema::hasColumn('f_docentete', 'doc_module')) {
                $table->dropIndex('idx_doc_module');
                $table->dropColumn('doc_module');
            }
        });
    }
};
