<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Support\Carbon;

class PruebaRecordatorioAulaSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $ahora = Carbon::now(config('app.timezone'));
        $inicio = $ahora->copy()->addMinutes(20);
        $fin = $inicio->copy()->addHours(2);

        if ($inicio->toDateString() !== $ahora->toDateString() || $fin->toDateString() !== $ahora->toDateString()) {
            $this->command?->warn('Escenario prueba.docente omitido: el bloque de dos horas cruzaría la medianoche.');

            return;
        }

        $user = $this->usuario('prueba.docente@unipamplona.edu.co', 'Prueba Docente Aula', 'Docente');
        $bloque = $this->bloque('PRUEBA-AULA', $inicio, $fin);
        $aula = Aula::query()
            ->whereHas('computadores')
            ->orderBy('id')
            ->get()
            ->first(fn (Aula $aula) => $this->aulaLibre($user, $aula, $ahora->toDateString(), $bloque));

        $reserva = $aula
            ? $this->guardarReserva($user, $bloque, $ahora->toDateString(), 'pendiente', aula: $aula)
            : null;

        if (! $aula) {
            $this->command?->warn('Escenario prueba.docente omitido: no hay aulas libres para ese bloque.');
        } elseif (! $reserva) {
            $this->command?->warn("Slot ocupado; se omite {$user->email} / aula {$aula->nombre}.");
        }

        $this->resumen($user, 'Docente', 'Aula completa inicia en 20 minutos', $reserva);
    }
}
