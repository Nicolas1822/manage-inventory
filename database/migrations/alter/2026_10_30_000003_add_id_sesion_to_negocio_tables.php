<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas cuyos registros pertenecen a un negocio (sesion).
     *
     * @var array<int, string>
     */
    private array $tablas = [
        'producto',
        'inventario',
        'factura',
        'venta',
        'venta_detalle',
        'clientes',
        'factura_electronica',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('id_sesion')->after('id')->constrained('sesion')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_sesion');
            });
        }
    }
};
