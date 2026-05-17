<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        Schema::table('f_docentete', function (Blueprint $table) {
            if (!Schema::hasColumn('f_docentete', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        Schema::table('f_reglements', function (Blueprint $table) {
            if (!Schema::hasColumn('f_reglements', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        Schema::table('f_stock', function (Blueprint $table) {
            if (!Schema::hasColumn('f_stock', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
        });

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('event_type', 100)->index();
                $table->string('entity_type', 100)->index();
                $table->string('entity_id', 64)->nullable()->index();
                $table->string('severity', 20)->default('info')->index();
                $table->json('payload')->nullable();
                $table->string('trace_id', 128)->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('outbox_events')) {
            Schema::create('outbox_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('event_name', 120)->index();
                $table->string('aggregate_type', 120)->index();
                $table->string('aggregate_id', 64)->index();
                $table->string('dedupe_key', 191)->unique();
                $table->json('payload');
                $table->string('status', 20)->default('pending')->index();
                $table->timestamp('available_at')->nullable()->index();
                $table->timestamp('processed_at')->nullable()->index();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        $duplicates = DB::table('journal_entries')
            ->select('reference_type', 'reference_id', DB::raw('COUNT(*) as c'))
            ->groupBy('reference_type', 'reference_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = DB::table('journal_entries')
                ->where('reference_type', $dup->reference_type)
                ->where('reference_id', $dup->reference_id)
                ->max('id');

            DB::table('journal_entries')
                ->where('reference_type', $dup->reference_type)
                ->where('reference_id', $dup->reference_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        try {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->unique(['reference_type', 'reference_id'], 'uq_journal_reference_once');
            });
        } catch (\Throwable) {
            // Index may already exist in upgraded environments.
        }
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('uq_journal_reference_once');
        });

        Schema::dropIfExists('outbox_events');
        Schema::dropIfExists('audit_logs');
    }
};
