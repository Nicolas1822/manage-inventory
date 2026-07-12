<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('rangos_numeracion', function (Blueprint $table) {
      $table->id();
      $table->string('document');
      $table->string('prefix', 5);
      $table->unsignedInteger('from')->nullable();
      $table->unsignedBigInteger('to')->nullable();
      $table->unsignedInteger('current');
      $table->string('resolution_number')->nullable();
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->string('technical_key')->nullable();
      $table->unsignedInteger('is_expired');
      $table->unsignedInteger('is_active');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('rangos_numeracion');
  }
};
