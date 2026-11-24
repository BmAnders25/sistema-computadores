<?php

namespace Database\Seeders;

use App\Models\Computador;
use Illuminate\Support\Carbon;

class PruebaRecordatorioInicioSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $ahora = Carbon::now(config('app.timezone'));
        $inicio = $ahora->copy()->addMinutes(20);
        $fin = $inicio->copy()->addHours(2);

        if ($inicio->toDateString() !== $ahora->toDateString() || $fin->toDateString() !== $ahora->toDateString()) {
            $this->command?->warn('Escenario prueba.inicio omitido: el bloque de dos horas cruzaría la medianoche.');

            return;
        }

        $user = $this->usuario('prueba.inicio@unipamplona.edu.co', 'Prueba Recordatorio Inicio', 'Estudiante');
        $bloque = $this->bloque('PRUEBA-INICIO', $inicio, $fin);
        $equipo = $this->equipoLibre($user, $ahora->toDateString(), $bloque);
        $reserva = $equipo
            ? $this->guardarReserva($user, $bloque, $ahora->toDateString(), 'pendiente', $equipo)
            : null;

        if (! $equipo) {
            $this->command?->warn('Escenario prueba.inicio omitido: no hay computador libre para ese bloque.');
        }

        $this->resumen($user, 'Estudiante', 'Inicio en 20 minutos', $reserva);
    }
}
