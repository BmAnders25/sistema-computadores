<?php

namespace Database\Seeders;

use App\Models\BloqueHorario;
use Illuminate\Database\Seeder;

class BloqueHorarioSeeder extends Seeder
{
    public function run(): void
    {
        $horarios = [
            ['07:00', '09:00'],
            ['09:00', '11:00'],
            ['11:00', '13:00'],
            ['13:00', '15:00'],
            ['15:00', '17:00'],
            ['17:00', '18:00'],
        ];

        foreach ($horarios as [$inicio, $fin]) {
            BloqueHorario::updateOrCreate(
                ['hora_inicio' => $inicio, 'hora_fin' => $fin],
                ['nombre' => "{$inicio} - {$fin}", 'activo' => true],
            );
        }
    }
}
