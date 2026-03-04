<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3);
            $table->timestamps();

            $table->unique(['product_id', 'currency']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
