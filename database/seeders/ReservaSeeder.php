<?php

namespace Database\Seeders;

use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $estudiante = User::role('Estudiante')->where('estado', 'activo')->firstOrFail();
        $docente = User::role('Docente')->where('estado', 'activo')->firstOrFail();
        $administrador = User::role('Administracion')->where('estado', 'activo')->firstOrFail();

        $bloques = BloqueHorario::where('activo', true)->orderBy('hora_inicio')->get();

        if ($bloques->count() < 3) {
            throw new \RuntimeException('Se requieren al menos tres bloques horarios activos para crear las reservas demo.');
        }

        $hoy = today()->toDateString();
        $manana = today()->addDay()->toDateString();
        $ayer = today()->subDay()->toDateString();
        $bloqueDeHoy = $bloques->first(function (BloqueHorario $bloque): bool {
            return $bloque->hora_inicio > now()->format('H:i:s');
        }) ?? $bloques->first();

        $reservasConfirmadas = [
            [$estudiante, 'DEMO-01', $bloqueDeHoy, $hoy],
            [$docente, 'DEMO-02', $bloques->first(), $manana],
            [$administrador, 'DEMO-03', $bloques->get(1), $manana],
        ];

        foreach ($reservasConfirmadas as [$usuario, $serial, $bloque, $fecha]) {
            $this->guardarReserva($usuario, $serial, $bloque, $fecha, 'confirmada');
        }

        $this->guardarReserva($estudiante, 'DEMO-10', $bloques->get(2), $manana, 'pendiente');

        $reservasEntregadas = [
            [$estudiante, 'DEMO-13', $bloques->first()],
            [$docente, 'DEMO-14', $bloques->get(1)],
        ];

        foreach ($reservasEntregadas as [$usuario, $serial, $bloque]) {
            $entregadoAt = Carbon::parse("{$ayer} {$bloque->hora_inicio}")->addMinutes(10);
            $this->guardarReserva($usuario, $serial, $bloque, $ayer, 'entregada', $entregadoAt);
        }

        $reservasDevueltas = [
            [$estudiante, 'DEMO-04', $bloques->first(), today()->subDays(2)],
            [$docente, 'DEMO-05', $bloques->get(1), today()->subDays(3)],
            [$administrador, 'DEMO-06', $bloques->first(), today()->subDays(4)],
            [$estudiante, 'DEMO-07', $bloques->get(1), today()->subDays(5)],
        ];

        foreach ($reservasDevueltas as [$usuario, $serial, $bloque, $fecha]) {
            $entregadoAt = Carbon::parse($fecha->toDateString().' '.$bloque->hora_inicio)->addMinutes(10);
            $devueltoAt = Carbon::parse($fecha->toDateString().' '.$bloque->hora_fin)->addMinutes(5);

            $this->guardarReserva(
                $usuario,
                $serial,
                $bloque,
                $fecha->toDateString(),
                'devuelta',
                $entregadoAt,
                $devueltoAt,
            );
        }

        $reservasCanceladas = [
            [$docente, 'DEMO-08', $bloques->first(), $ayer],
            [$administrador, 'DEMO-09', $bloques->get(1), $ayer],
        ];

        foreach ($reservasCanceladas as [$usuario, $serial, $bloque, $fecha]) {
            $this->guardarReserva($usuario, $serial, $bloque, $fecha, 'cancelada');
        }
    }

    private function guardarReserva(
        User $usuario,
        string $serial,
        BloqueHorario $bloque,
        string $fecha,
        string $estado,
        ?Carbon $entregadoAt = null,
        ?Carbon $devueltoAt = null,
    ): void {
        $computador = Computador::where('serial', $serial)->firstOrFail();
        $activa = in_array($estado, Reserva::ACTIVAS, true);

        Reserva::updateOrCreate(
            [
                'user_id' => $usuario->id,
                'computador_id' => $computador->id,
                'aula_id' => null,
                'bloque_horario_id' => $bloque->id,
                'fecha' => $fecha,
            ],
            [
                'estado' => $estado,
                'entregado_at' => $entregadoAt,
                'devuelto_at' => $devueltoAt,
                'clave_unica' => $activa
                    ? Reserva::claveEquipo($computador->id, $fecha, $bloque->id)
                    : null,
            ],
        );
    }
}
