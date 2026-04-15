<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
 public function run(): void
{
    $turnos = [
        ['name' => 'Mañana'], 
        ['name' => 'Tarde'], 
        ['name' => 'Noche']
    ];
    foreach ($turnos as $t) {
        \App\Models\Shift::firstOrCreate($t);
    }
}
}