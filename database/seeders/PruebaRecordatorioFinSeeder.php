<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

class PruebaRecordatorioFinSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $ahora = Carbon::now(config('app.timezone'));
        $inicio = $ahora->copy()->subHour();
        $fin = $ahora->copy()->addMinutes(10);

        if ($inicio->toDateString() !== $ahora->toDateString() || $fin->toDateString() !== $ahora->toDateString()) {
            $this->command?->warn('Escenario prueba.fin omitido: el bloque de prueba cruzaría la medianoche.');

            return;
        }

        $user = $this->usuario('prueba.fin@unipamplona.edu.co', 'Prueba Recordatorio Fin', 'Estudiante');
        $bloque = $this->bloque('PRUEBA-FIN', $inicio, $fin);
        $equipo = $this->equipoLibre($user, $ahora->toDateString(), $bloque);
        $reserva = $equipo
            ? $this->guardarReserva(
                $user,
                $bloque,
                $ahora->toDateString(),
                'entregada',
                $equipo,
                entregadoAt: $ahora->copy()->subMinutes(55)
            )
            : null;

        if (! $equipo) {
            $this->command?->warn('Escenario prueba.fin omitido: no hay computador libre para ese bloque.');
        }

        $this->resumen($user, 'Estudiante', 'Fin en 10 minutos', $reserva);
    }
}
