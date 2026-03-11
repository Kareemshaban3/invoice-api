<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('site_settings', function (Blueprint $table) {
      $table->id();

      $table->string('site_name')->nullable();
      $table->string('site_url')->nullable();
      $table->string('site_email')->nullable();

      $table->unsignedInteger('items_per_page')->default(10);

      $table->string('default_currency', 3)->default('EGP');

      $table->string('logo_path')->nullable();

      $table->longText('invoice_footer')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('site_settings');
  }
};
