<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mark deprecated and legacy tables.
     * These tables are kept for historical/migration purposes but are not actively used.
     */
    public function up(): void
    {
        // compta_ecritures: Deprecated - replaced by journal_entries table
        // This table was used in early accounting implementation but is no longer maintained
        // All new accounting data should go to journal_entries instead

        // logs: Deprecated - replaced by audit_logs and outbox_events
        // This legacy log table is no longer used
        // All audit logging should use audit_logs table instead

        // Note: These tables are retained for data backup and historical purposes
        // Consider archiving and removing after data migration
    }

    public function down(): void
    {
        // No action needed for down migration
    }
};
