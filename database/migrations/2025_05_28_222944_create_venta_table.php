<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codigo_venta');
            $table->date('fecha_venta');
            $table->unsignedBigInteger('total_venta');
            $table->boolean('estado_factura_electronica')
                ->default(false)
                ->comment('0 no se a creado la factura electronica de la venta y 1 si se a creado la FE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};
