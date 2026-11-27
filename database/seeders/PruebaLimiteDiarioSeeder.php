<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

class PruebaLimiteDiarioSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $user = $this->usuario('prueba.limite@unipamplona.edu.co', 'Prueba Límite Diario', 'Estudiante');
        $mañana = Carbon::now(config('app.timezone'))->addDay()->toDateString();
        $horarios = [
            ['07:00', '09:00'],
            ['09:00', '11:00'],
            ['11:00', '13:00'],
            ['13:00', '15:00'],
            ['15:00', '17:00'],
        ];
        $usados = [];

        foreach ($horarios as $indice => [$horaInicio, $horaFin]) {
            $inicio = Carbon::parse($mañana.' '.$horaInicio, config('app.timezone'));
            $fin = Carbon::parse($mañana.' '.$horaFin, config('app.timezone'));
            $bloque = $this->bloque(sprintf('PRUEBA-LIMITE-%02d', $indice + 1), $inicio, $fin);
            $equipo = $this->equipoLibre($user, $mañana, $bloque, $usados);
            $reserva = $equipo
                ? $this->guardarReserva($user, $bloque, $mañana, 'pendiente', $equipo)
                : null;

            if ($equipo) {
                $usados[] = $equipo->id;
            } else {
                $this->command?->warn('No se pudo crear el slot '.($indice + 1).' de prueba.limite.');
            }

            $this->resumen($user, 'Estudiante', 'Reserva '.($indice + 1).' de 5 para mañana', $reserva);
        }
    }
}
