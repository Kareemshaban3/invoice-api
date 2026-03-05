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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('city');
            $table->enum('country', [
                'Egypt',
                'Saudi Arabia',
                'Oman',
                "Jordan",
                "Bahrain",
                "Algeria",
                "Sudan",
                "Syria",
                "Palestine",
                "Iraq",
                "Qatar",
                "Kuwait",
                "Lebanon",
                "Libya",
                "Morocco",
                "Yemen",
                "Tunisia",
                "Somalia",
            ])->nullable();

            $table->text('address')->nullable();


            $table->string('tax_number')->nullable();

            $table->unsignedSmallInteger('payment_terms_days')->default(0); // 30/60
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('opening_balance', 12, 2)->default(0);

            $table->enum('default_payment_method', ['cash', 'transfer', 'card', 'credit'])
                ->default('transfer');

            // بيانات البنك
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();

            $table->enum('status', ['active', 'suspended', 'archived'])->default('active');
            $table->text('notes')->nullable();


            $table->timestamps();

            $table->index(['name', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
