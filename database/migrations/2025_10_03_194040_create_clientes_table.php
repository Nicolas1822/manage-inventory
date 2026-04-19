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
    Schema::create('clientes', function (Blueprint $table) {
      $table->id();
      $table->integer('id_documento_identificacion');
      $table->string('identificacion');
      $table->integer('dv')->nullable();
      $table->string('empresa')->nullable();
      $table->string('nombre_comercial')->nullable();
      $table->string('nombres')->nullable();
      $table->string('direccion')->nullable();
      $table->string('email')->nullable();
      $table->string('n_celular')->nullable();
      $table->integer('tipo_organizacion');
      $table->foreignId('id_tributo')->constrained('tributos');
      $table->foreignId('id_municipio')->constrained('municipios');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('clientes');
  }
};
