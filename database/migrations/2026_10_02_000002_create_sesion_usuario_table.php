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
        Schema::create('sesion_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sesion')->constrained('sesion');
            $table->foreignId('id_user')->constrained('users');
            // $table->string('rol_en_sesion', 50)->default('observador')->comment('admin, operador, observador');
            $table->timestamps();

            $table->unique(['id_sesion', 'id_user']);
            $table->index(['id_sesion', 'id_user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesion_usuario');
    }
};
