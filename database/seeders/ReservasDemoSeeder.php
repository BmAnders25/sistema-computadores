<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $aulas = collect([
            ['Bloque A', 'LAB-1'],
            ['Bloque A', 'LAB-2'],
            ['Bloque B', 'LAB-3'],
            ['Bloque B', 'LAB-4'],
        ]);

        $aulasCreadas = $aulas->map(function (array $datos) {
            return Aula::firstOrCreate(
                ['bloque' => $datos[0], 'nombre' => $datos[1]],
                ['capacidad' => 30, 'estado' => 'activo']
            );
        });

        $bloques = collect([
            ['Bloque 1 - Mañana', '07:00', '09:00'],
            ['Bloque 2 - Mañana', '09:00', '11:00'],
            ['Bloque 3 - Tarde', '13:00', '15:00'],
            ['Bloque 4 - Tarde', '15:00', '17:00'],
        ])->map(function (array $datos) {
            return BloqueHorario::firstOrCreate(
                ['nombre' => $datos[0]],
                [
                    'hora_inicio' => $datos[1],
                    'hora_fin' => $datos[2],
                    'activo' => true,
                ]
            );
        });

        $computadores = collect();
        foreach ($aulasCreadas as $indiceAula => $aula) {
            for ($indiceEquipo = 1; $indiceEquipo <= 3; $indiceEquipo++) {
                $serial = 'DEMO-' . str_pad((string) (($indiceAula * 3) + $indiceEquipo), 3, '0', STR_PAD_LEFT);
                $computadores->push(Computador::firstOrCreate(
                    ['serial' => $serial],
                    [
                        'aula_id' => $aula->id,
                        'tipo_equipo' => 'escritorio',
                        'marca' => 'Dell',
                        'modelo' => 'OptiPlex 7010',
                        'procesador' => 'Intel Core i5',
                        'ram_gb' => 16,
                        'almacenamiento' => '512 GB SSD',
                        'sistema_operativo' => 'Windows 11 Pro',
                        'estado' => 'disponible',
                        'condicion' => 'bueno',
                        'observaciones' => 'Equipo creado por el seeder de demostración.',
                    ]
                ));
            }
        }

        $usuario = User::firstOrCreate(
            ['email' => 'demo.reportes@unipamplona.edu.co'],
            [
                'name' => 'Usuario Demostración',
                'password' => 'password',
                'estado' => 'activo',
                'two_factor_enabled' => false,
            ]
        );

        Reserva::where('user_id', $usuario->id)->where(function ($query) {
            $query->where('clave_unica', 'like', 'demo:%')
                ->orWhereNull('clave_unica');
        })->delete();

        $estados = ['pendiente', 'confirmada', 'entregada', 'devuelta', 'cancelada', 'no_show'];
        $ahora = now();

        DB::transaction(function () use ($aulasCreadas, $bloques, $computadores, $usuario, $estados, $ahora) {
            for ($indice = 1; $indice <= 300; $indice++) {
                $fecha = $ahora->copy()->subDays((($indice * 7) % 60))->toDateString();
                $bloque = $bloques[($indice - 1) % $bloques->count()];
                $estado = $estados[($indice * 5) % count($estados)];
                $usarAulaCompleta = $indice % 5 === 0;

                $aulaId = null;
                $computadorId = null;

                if ($usarAulaCompleta) {
                    $aulaId = $aulasCreadas[($indice - 1) % $aulasCreadas->count()]->id;
                } else {
                    $computadorId = $computadores[($indice - 1) % $computadores->count()]->id;
                }

                $permiteClave = in_array($estado, ['pendiente', 'confirmada', 'entregada'], true);
                $clave = $permiteClave
                    ? 'demo:' . $indice . ':' . $fecha . ':' . $bloque->id
                    : null;

                Reserva::create([
                    'user_id' => $usuario->id,
                    'computador_id' => $computadorId,
                    'aula_id' => $aulaId,
                    'bloque_horario_id' => $bloque->id,
                    'fecha' => $fecha,
                    'estado' => $estado,
                    'entregado_at' => $estado === 'entregada' ? Carbon::parse($fecha)->setTime(8, 0) : null,
                    'devuelto_at' => $estado === 'devuelta' ? Carbon::parse($fecha)->setTime(16, 0) : null,
                    'clave_unica' => $clave,
                ]);
            }
        });

        $this->command->info('ReservasDemoSeeder: 300 reservas de demostración creadas.');
    }
}
