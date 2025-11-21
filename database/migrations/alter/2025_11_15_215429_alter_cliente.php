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
    if (Schema::hasTable('clientes')) {
      Schema::table('clientes', function (Blueprint $table) {
        $table->foreignId('id_usuario')->after('id_municipio')->constrained('users');
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
