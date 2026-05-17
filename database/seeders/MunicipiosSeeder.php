<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MunicipiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/municipios.json'));
        $data = json_decode($json, true);

        if (isset($data['municipalities'])) {
            $municipios = collect($data['municipalities'])->map(function ($municipio) {
                return [
                    'municipality_code' => $municipio['code'],
                    'municipality_name' => $municipio['name'],
                    'department_code' => $municipio['department']['code'],
                    'department_name' => $municipio['department']['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            foreach ($municipios->chunk(100) as $chunk) {
                \DB::table('municipios')->insert($chunk->toArray());
            }
        }
    }
}
