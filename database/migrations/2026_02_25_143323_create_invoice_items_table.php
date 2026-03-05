<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->enum('item_type', ['product', 'service'])->default('product');

            $table->foreignId('product_id')->nullable()
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('product_name');
            $table->text('product_description')->nullable();

            $table->decimal('unit_price', 12, 2)->default(0);

            $table->decimal('quantity', 12, 2)->default(1);

            $table->enum('discount_type', ['none', 'amount', 'percent'])->default('none');
            $table->decimal('discount_value', 12, 2)->default(0);

            $table->enum('tax_type', ['no_tax', 'exclusive', 'inclusive'])->default('no_tax');
            $table->decimal('tax_rate', 5, 2)->default(0);

            $table->decimal('line_total', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
