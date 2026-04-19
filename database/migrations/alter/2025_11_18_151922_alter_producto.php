<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    if (Schema::hasTable('producto')) {
      Schema::table('producto', function (Blueprint $table) {
        $table->unsignedFloat('porcentaje_descuento')->after('cantidad_vendida');
        $table->string('porcentaje_impuesto')->after('porcentaje_descuento');
        $table->unsignedInteger('codigo_estandar_id')->after('porcentaje_impuesto');
        $table->boolean('excluido')->after('codigo_estandar_id');
        $table->foreignId('id_tributo')->nullable(true)->after('excluido')->constrained('tributos');
        $table->foreignId('id_unidad_medida')->nullable(true)->after('id_tributo')->constrained('unidades_de_medida');
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    //
  }
};
