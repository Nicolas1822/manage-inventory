<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un usuario pertenece a un solo negocio.
     */
    public function up(): void
    {
        Schema::table('sesion_usuario', function (Blueprint $table) {
            $table->unique('id_user');
        });
    }

    public function down(): void
    {
        Schema::table('sesion_usuario', function (Blueprint $table) {
            $table->dropUnique(['id_user']);
        });
    }
};
