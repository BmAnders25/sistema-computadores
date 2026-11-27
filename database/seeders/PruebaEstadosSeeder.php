<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

class PruebaEstadosSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $user = $this->usuario('prueba.estados@unipamplona.edu.co', 'Prueba Estados', 'Estudiante');
        $mañana = Carbon::now(config('app.timezone'))->addDay()->toDateString();
        $estados = ['pendiente', 'entregada', 'devuelta', 'cancelada', 'no_show'];
        $usados = [];

        foreach ($estados as $indice => $estado) {
            $inicio = Carbon::parse($mañana.' '.sprintf('%02d:00', 7 + ($indice * 2)), config('app.timezone'));
            $fin = $inicio->copy()->addHours(2);
            $bloque = $this->bloque('PRUEBA-ESTADO-'.strtoupper($estado), $inicio, $fin);
            $equipo = $this->equipoLibre($user, $mañana, $bloque, $usados);
            $entregadoAt = in_array($estado, ['entregada', 'devuelta'], true)
                ? $inicio->copy()->addMinutes(5)
                : null;
            $devueltoAt = $estado === 'devuelta' ? $inicio->copy()->addHour() : null;
            $reserva = $equipo
                ? $this->guardarReserva($user, $bloque, $mañana, $estado, $equipo, entregadoAt: $entregadoAt, devueltoAt: $devueltoAt)
                : null;

            if ($equipo) {
                $usados[] = $equipo->id;
            } else {
                $this->command?->warn("No se pudo crear el escenario con estado {$estado}.");
            }

            $this->resumen($user, 'Estudiante', 'Estado '.$estado, $reserva);
        }
    }
}
