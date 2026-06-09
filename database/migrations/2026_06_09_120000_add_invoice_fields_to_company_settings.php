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
        Schema::table('company_settings', function (Blueprint $table) {
            // Add invoice-related fields
            if (!Schema::hasColumn('company_settings', 'company_registration')) {
                $table->string('company_registration')->nullable()->after('company_if');
            }

            if (!Schema::hasColumn('company_settings', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('company_registration');
            }

            if (!Schema::hasColumn('company_settings', 'payment_terms')) {
                $table->text('payment_terms')->nullable()->after('invoice_footer');
            }

            if (!Schema::hasColumn('company_settings', 'company_notes')) {
                $table->text('company_notes')->nullable()->after('payment_terms');
            }

            if (!Schema::hasColumn('company_settings', 'invoice_show_signature')) {
                $table->boolean('invoice_show_signature')->default(true)->after('company_notes');
            }

            if (!Schema::hasColumn('company_settings', 'thermal_auto_cut')) {
                $table->boolean('thermal_auto_cut')->default(true)->after('invoice_show_signature');
            }

            if (!Schema::hasColumn('company_settings', 'thermal_full_cut')) {
                $table->boolean('thermal_full_cut')->default(false)->after('thermal_auto_cut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_registration',
                'tax_id',
                'payment_terms',
                'company_notes',
                'invoice_show_signature',
                'thermal_auto_cut',
                'thermal_full_cut',
            ]);
        });
    }
};
