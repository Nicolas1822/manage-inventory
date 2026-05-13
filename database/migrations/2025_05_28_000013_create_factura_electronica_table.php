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
    Schema::create('factura_electronica', function (Blueprint $table) {
      $table->id();
      $table->foreignId('id_rango_numeracion')->constrained('rangos_numeracion');
      $table->string('documento');
      $table->string('codigo_referencia');
      $table->string('observacion', 250);
      $table->string('forma_pago');
      $table->date('fecha_vencimiento_factura')->nullable(true);
      $table->unsignedInteger('codigo_metodo_pago');
      $table->string('tipo_operacion');
      $table->string('numero_factura');
      $table->foreignId('id_cliente')->constrained('clientes');
      $table->foreignId('id_venta')->constrained('venta');
      $table->foreignId('id_usuario')->constrained('users');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('factura_electronica');
  }
};
