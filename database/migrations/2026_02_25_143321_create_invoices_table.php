<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('number')->unique();
            $table->date('date');
            $table->date('due_date')->nullable();

            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->decimal('paid', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->decimal('tax_total', 10, 2)->default(0);




            $table->enum('payment_method', ['cash', 'transfer', 'card', 'credit'])->default('cash');
            $table->enum('payment_status', ['draft', 'unpaid', 'partial', 'paid', 'cancelled'])->default('draft');

            $table->timestamps();

            $table->index(['client_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
