<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TributosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('seeders/data/tributos.json'));
        $data = json_decode($json, true);

        if (isset($data['tributos'])) {
            $tributos = collect($data['tributos'])->map(function ($tributo) {
                return [
                    'code' => $tributo['code'],
                    'name' => $tributo['name'],
                    'description' => $tributo['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            foreach ($tributos->chunk(100) as $chunk) {
                DB::table('tributos')->insert($chunk->toArray());
            }
        }
    }
}
