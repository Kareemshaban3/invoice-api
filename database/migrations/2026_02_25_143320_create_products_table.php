<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            $table->foreignId('category_id')->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('supplier_id')->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);

            $table->enum('unit', [
                'piece',
                'carton',
                'meter',
                'kilogram',
                'half_kilo',
                'hour',
                'service'
            ])->default('piece');

            $table->decimal('cost_price', 12, 2)->default(0);


            $table->enum('default_tax_type', ['no_tax', 'exclusive', 'inclusive'])->default('no_tax');
            $table->decimal('default_tax_rate', 5, 2)->default(0); 

            $table->enum('status', ['active', 'suspended', 'archived'])->default('active');

            $table->timestamps();

            $table->index(['name', 'sku', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
