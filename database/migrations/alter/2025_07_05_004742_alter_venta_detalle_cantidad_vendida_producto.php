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
    if (Schema::hasTable('venta_detalle')) {
      Schema::table('venta_detalle', function (Blueprint $table) {
        $table->unsignedInteger('cantidad_vendida_producto')->nullable()->after('id_usuario');
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
