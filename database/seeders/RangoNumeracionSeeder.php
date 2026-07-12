<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RangoNumeracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/rangos_numeracion.json'));
        $data = json_decode($json, true);

        if (isset($data['rangos_numeracion'])) {
            $rangos = collect($data['rangos_numeracion'])->map(function (array $rango): array {
                return [
                    'id' => $rango['id'],
                    'document' => $rango['document'],
                    'prefix' => $rango['prefix'],
                    'from' => $rango['from'],
                    'to' => $rango['to'],
                    'current' => $rango['current'],
                    'resolution_number' => $rango['resolution_number'],
                    'start_date' => $rango['start_date'],
                    'end_date' => $rango['end_date'],
                    'technical_key' => $rango['technical_key'],
                    'is_expired' => $rango['is_expired'] ? 1 : 0,
                    'is_active' => $rango['is_active'] ? 1 : 0,
                    'created_at' => isset($rango['created_at']) ? Carbon::parse($rango['created_at']) : now(),
                    'updated_at' => isset($rango['updated_at']) ? Carbon::parse($rango['updated_at']) : now(),
                ];
            });

            foreach ($rangos->chunk(100) as $chunk) {
                DB::table('rangos_numeracion')->insert($chunk->toArray());
            }
        }
    }
}
