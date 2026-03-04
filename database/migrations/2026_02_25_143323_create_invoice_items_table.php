<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('invoice_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

      $table->foreignId('product_id')->constrained()->restrictOnDelete();

      $table->string('product_name');
      $table->text('product_description')->nullable();
      $table->decimal('unit_price', 12, 2)->default(0);

      $table->decimal('quantity', 12, 2)->default(1);
      $table->decimal('line_total', 12, 2)->default(0);

      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('invoice_items');
  }
};
