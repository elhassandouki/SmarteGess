<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('price_mad', 10, 2)->default(0);
            $table->unsignedInteger('max_users')->default(2);
            $table->unsignedInteger('max_documents_per_month')->default(200);
            $table->unsignedInteger('max_storage_gb')->default(2);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('subscription_plan_id')->index();
            $table->string('status', 20)->default('trial')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index()->unique();
            $table->string('company_name');
            $table->string('company_ice')->nullable();
            $table->string('company_if')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('invoice_prefix', 20)->default('FAC');
            $table->string('invoice_footer')->nullable();
            $table->string('primary_color', 20)->default('#0d6efd');
            $table->timestamps();
        });

        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('document_code', 10)->index();
            $table->unsignedInteger('year')->index();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'document_code', 'year'], 'uq_invoice_sequences_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
