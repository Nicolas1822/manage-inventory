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
      DB::statement('ALTER TABLE venta_detalle ADD COLUMN cantidad_vendida_producto INT NULL AFTER id_usuario');
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
