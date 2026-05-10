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
    Schema::create('inventario', function (Blueprint $table) {
      $table->id();
      $table->unsignedInteger('cantidad_disponible');
      $table->foreignId('id_producto')->constrained('producto')->onDelete('cascade');
      $table->foreignId('id_usuario')->constrained('users');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventario');
  }
};
