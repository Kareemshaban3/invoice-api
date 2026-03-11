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
        Schema::create('representatives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
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
            $table->string('commercial_register')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('opening_balance', 12, 2)->default(0);

            $table->enum('default_payment_method', ['cash', 'transfer', 'card', 'credit'])
                ->default('cash');


            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->index(['email', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representatives');
    }
};
