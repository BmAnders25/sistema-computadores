<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\Computador;
use Illuminate\Database\Seeder;

class ComputadorSeeder extends Seeder
{
    public function run(): void
    {
        $aulas = Aula::all()->keyBy(function (Aula $aula): string {
            return "{$aula->bloque}|{$aula->nombre}";
        });

        $computadores = [
            ['DEMO-01', 'Bloque A', 'LAB-1', 'escritorio', 'Dell', 'OptiPlex 7010', 'Intel Core i5-13500', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'excelente'],
            ['DEMO-02', 'Bloque A', 'LAB-1', 'escritorio', 'HP', 'ProDesk 400 G9', 'Intel Core i5-12500', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-03', 'Bloque A', 'LAB-1', 'escritorio', 'Lenovo', 'ThinkCentre M70q', 'Intel Core i5-12400T', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-04', 'Bloque A', 'LAB-2', 'escritorio', 'ASUS', 'ExpertCenter D5', 'Intel Core i5-13400', 16, '1 TB SSD', 'Windows 11 Pro', 'disponible', 'excelente'],
            ['DEMO-05', 'Bloque A', 'LAB-2', 'escritorio', 'Acer', 'Veriton X4', 'Intel Core i5-12400', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-06', 'Bloque A', 'LAB-2', 'all_in_one', 'HP', 'ProOne 440 G9', 'Intel Core i5-12500T', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-07', 'Bloque B', 'LAB-1', 'escritorio', 'Dell', 'OptiPlex 7090', 'Intel Core i7-11700', 16, '1 TB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-08', 'Bloque B', 'LAB-1', 'portatil', 'Lenovo', 'ThinkPad E14 Gen 5', 'AMD Ryzen 5 7530U', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'excelente'],
            ['DEMO-09', 'Bloque B', 'LAB-1', 'escritorio', 'HP', 'EliteDesk 800 G6', 'Intel Core i5-10500', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-10', 'Bloque B', 'LAB-2', 'portatil', 'ASUS', 'ExpertBook B1', 'Intel Core i5-1235U', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-11', 'Bloque B', 'LAB-2', 'escritorio', 'Lenovo', 'ThinkCentre M80s', 'Intel Core i7-12700', 32, '1 TB SSD', 'Windows 11 Pro', 'disponible', 'excelente'],
            ['DEMO-12', 'Bloque B', 'LAB-2', 'escritorio', 'Acer', 'Veriton N4', 'Intel Core i5-1240P', 16, '512 GB SSD', 'Windows 11 Pro', 'disponible', 'bueno'],
            ['DEMO-13', 'Bloque A', 'LAB-1', 'escritorio', 'Dell', 'OptiPlex 7010', 'Intel Core i5-13500', 16, '512 GB SSD', 'Windows 11 Pro', 'prestado', 'bueno'],
            ['DEMO-14', 'Bloque B', 'LAB-1', 'escritorio', 'HP', 'ProDesk 400 G9', 'Intel Core i5-12500', 16, '512 GB SSD', 'Windows 11 Pro', 'prestado', 'bueno'],
            ['DEMO-15', 'Bloque B', 'LAB-2', 'escritorio', 'Lenovo', 'ThinkCentre M70q', 'Intel Core i5-12400T', 16, '512 GB SSD', 'Windows 11 Pro', 'mantenimiento', 'regular'],
        ];

        foreach ($computadores as [
            $serial,
            $bloque,
            $laboratorio,
            $tipo,
            $marca,
            $modelo,
            $procesador,
            $ram,
            $almacenamiento,
            $sistemaOperativo,
            $estado,
            $condicion,
        ]) {
            $aula = $aulas->get("{$bloque}|{$laboratorio}");

            if (! $aula) {
                throw new \RuntimeException("No existe el aula {$bloque} {$laboratorio} para el computador {$serial}.");
            }

            Computador::updateOrCreate(
                ['serial' => $serial],
                [
                    'aula_id' => $aula->id,
                    'bloque' => $bloque,
                    'laboratorio_aula' => $laboratorio,
                    'tipo_equipo' => $tipo,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'procesador' => $procesador,
                    'ram_gb' => $ram,
                    'almacenamiento' => $almacenamiento,
                    'sistema_operativo' => $sistemaOperativo,
                    'estado' => $estado,
                    'condicion' => $condicion,
                    'observaciones' => $estado === 'mantenimiento' ? 'Equipo en revision tecnica.' : null,
                ],
            );
        }
    }
}
