<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    public function run(): void
    {
        $aulas = [
            ['Bloque A', 'LAB-1', 30],
            ['Bloque A', 'LAB-2', 24],
            ['Bloque B', 'LAB-1', 30],
            ['Bloque B', 'LAB-2', 24],
        ];

        foreach ($aulas as [$bloque, $nombre, $capacidad]) {
            Aula::updateOrCreate(
                ['bloque' => $bloque, 'nombre' => $nombre],
                ['capacidad' => $capacidad, 'estado' => 'activo'],
            );
        }
    }
}
