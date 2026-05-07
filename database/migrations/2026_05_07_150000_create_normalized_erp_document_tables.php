<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_documents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->enum('document_type', ['quotation', 'order', 'delivery_note', 'invoice', 'credit_note']);
            $table->enum('status', ['draft', 'confirmed', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $table->date('document_date')->index();
            $table->foreignId('customer_id')->constrained('f_comptet')->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('sales_documents')->nullOnDelete();
            $table->decimal('subtotal_ht', 18, 2)->default(0);
            $table->decimal('total_tva', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_document_id')->constrained('sales_documents')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('f_articles')->cascadeOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_price_ht', 18, 5);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tva_percent', 5, 2)->default(0);
            $table->decimal('line_total_ht', 18, 2);
            $table->decimal('line_total_ttc', 18, 2);
            $table->timestamps();
        });

        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->enum('document_type', ['purchase_order', 'supplier_invoice', 'supplier_credit_note']);
            $table->enum('status', ['draft', 'confirmed', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $table->date('document_date')->index();
            $table->foreignId('supplier_id')->constrained('f_comptet')->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('purchase_documents')->nullOnDelete();
            $table->decimal('subtotal_ht', 18, 2)->default(0);
            $table->decimal('total_tva', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_document_id')->constrained('purchase_documents')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('f_articles')->cascadeOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_price_ht', 18, 5);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tva_percent', 5, 2)->default(0);
            $table->decimal('line_total_ht', 18, 2);
            $table->decimal('line_total_ttc', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_document_lines');
        Schema::dropIfExists('purchase_documents');
        Schema::dropIfExists('sales_document_lines');
        Schema::dropIfExists('sales_documents');
    }
};
