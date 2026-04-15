<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Semester;

class SemesterSeeder extends Seeder
{
   public function run(): void
{
    $semestres = [
        ['name' => '1er Semestre'], ['name' => '2do Semestre'], ['name' => '3er Semestre'],
        ['name' => '4to Semestre'], ['name' => '5to Semestre'], ['name' => '6to Semestre'],
        ['name' => '7mo Semestre'], ['name' => '8vo Semestre'], ['name' => '9no Semestre']
    ];
    foreach ($semestres as $s) {
        \App\Models\Semester::firstOrCreate($s);
    }
}
}