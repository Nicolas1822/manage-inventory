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
        'CREATE TRIGGER update_before_delete_cantidad_vendida
        BEFORE DELETE ON venta_detalle
        FOR EACH ROW
        BEGIN
            UPDATE producto
            SET
            cantidad_vendida = cantidad_vendida - OLD.cantidad_vendida_producto
            WHERE
            id = OLD.id_producto;
        END'
      );

      DB::unprepared(
        'CREATE TRIGGER update_before_delete_cantidad_disponible
        BEFORE DELETE ON venta_detalle
        FOR EACH ROW
        BEGIN
            UPDATE inventario
            SET
            cantidad_disponible = cantidad_disponible + OLD.cantidad_vendida_producto
            WHERE
            id_producto = OLD.id_producto;
        END'
      );
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    //
  }
};
