<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadDeMedidaSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $json = file_get_contents(database_path('seeders/data/unidades_medida.json'));
    $data = json_decode($json, true);

    if (isset($data['unitMeasures'])) {
      $unidades = collect($data['unitMeasures'])->map(function ($unidad) {
        return [
          'code' => $unidad['code'],
          'name' => $unidad['name'],
          'created_at' => now(),
          'updated_at' => now(),
        ];
      });

      foreach ($unidades->chunk(100) as $chunk) {
        DB::table('unidades_de_medida')->insert($chunk->toArray());
      }
    }
  }
}
