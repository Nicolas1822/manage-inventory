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
    Schema::create('producto', function (Blueprint $table) {
      $table->id();
      $table->unsignedInteger('lote_producto');
      $table->string('nombre_producto');
      $table->unsignedBigInteger('precio_unidad');
      $table->string('marca');
      $table->unsignedInteger('cantidad_total_inicial');
      $table->unsignedInteger('cantidad_vendida')->nullable();
      $table->unsignedFloat('porcentaje_descuento');
      $table->string('porcentaje_impuesto');
      $table->unsignedInteger('codigo_estandar_id');
      $table->boolean('excluido');
      $table->boolean('estado_agotado')->default(false);
      $table->foreignId('id_tributo')->nullable(true)->constrained('tributos');
      $table->foreignId('id_unidad_medida')->nullable(true)->constrained('unidades_de_medida');
      $table->foreignId('id_factura')->constrained('factura');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('producto');
  }
};
