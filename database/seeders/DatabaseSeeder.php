<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Llamamos a los seeders de catálogos base
        // Asegúrate de que SemesterSeeder y ShiftSeeder existan
        $this->call([
            SemesterSeeder::class,
            ShiftSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
        ]);

        // 2. Crear o actualizar el usuario administrador
        User::updateOrCreate(
            ['email' => 'admin@mecatronica.com'], // Condición de búsqueda
            [
                'name'     => 'Admin Mecatrónico',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}