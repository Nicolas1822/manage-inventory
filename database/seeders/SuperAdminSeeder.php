<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $name = env('SUPERADMIN_NAME');
        $password = env('SUPERADMIN_PASSWORD');

        if (blank($email) || blank($name) || blank($password)) {
            $this->command?->warn('SuperAdminSeeder omitido: define SUPERADMIN_EMAIL, SUPERADMIN_NAME y SUPERADMIN_PASSWORD.');

            return;
        }

        $usuario = User::firstOrNew(['email' => $email]);
        $usuario->name = $name;
        $usuario->password = $password;
        $usuario->forceFill(['es_superadmin' => true])->save();
    }
}
