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
    Schema::table('venta_detalle', function (Blueprint $table) {
      DB::unprepared(
        'CREATE TRIGGER trg_after_update_venta_detalle_update_cantidad_vendida
            AFTER UPDATE ON venta_detalle
            FOR EACH ROW
            BEGIN
                DECLARE diferencia INT;
                SET diferencia = NEW.cantidad_vendida_producto - OLD.cantidad_vendida_producto;

                UPDATE producto
                SET cantidad_vendida = cantidad_vendida + diferencia
                WHERE id = NEW.id_producto;
            END;'
      );

      DB::unprepared(
        'CREATE TRIGGER trg_after_update_venta_detalle_update_cantidad_disponible
            AFTER UPDATE ON venta_detalle
            FOR EACH ROW
            BEGIN
                UPDATE inventario
                SET
                cantidad_disponible = cantidad_disponible - (NEW.cantidad_vendida_producto - OLD.cantidad_vendida_producto)
                WHERE
                id_producto = NEW.id_producto;
            END;'
      );
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('venta_detalle', function (Blueprint $table) {
      //
    });
  }
};
