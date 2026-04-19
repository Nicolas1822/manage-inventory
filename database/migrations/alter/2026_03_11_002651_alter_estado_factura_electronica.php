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
    if (Schema::hasTable('venta')) {
      Schema::table('venta', function (Blueprint $table) {
        $table->boolean('estado_factura_electronica')
          ->default(false)
          ->comment('0 no se a creado la factura electronica de la venta y 1 si se a creado la FE')
          ->after('total_venta');
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
