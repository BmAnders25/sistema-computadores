<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Notifications\RecordatorioFinReserva;
use App\Notifications\RecordatorioInicioReserva;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnviarRecordatoriosReservas extends Command
{
    protected $signature = 'reservas:recordatorios';

    protected $description = 'Envía recordatorios de inicio y fin de reservas próximas.';

    public function handle(): int
    {
        $ahora = Carbon::now(config('app.timezone'));
        $reservas = Reserva::query()
            ->whereDate('fecha', $ahora->toDateString())
            ->whereIn('estado', ['pendiente', 'entregada'])
            ->with(['user', 'bloque', 'computador', 'aula'])
            ->get();

        $iniciosEnviados = 0;
        $finesEnviados = 0;

        foreach ($reservas as $reserva) {
            if ($reserva->user?->estado !== 'activo' || ! $reserva->bloque) {
                continue;
            }

            if ($reserva->estado === 'pendiente') {
                $inicio = Carbon::parse(
                    $reserva->fecha->toDateString() . ' ' . $reserva->bloque->hora_inicio,
                    config('app.timezone')
                );

                if ($this->estaDentroDeVentana($ahora, $inicio, 30)
                    && $this->enviarUnaVez($reserva, 'recordatorio_inicio_enviado_at', RecordatorioInicioReserva::class)) {
                    $iniciosEnviados++;
                }
            }

            if ($reserva->estado === 'entregada') {
                $fin = Carbon::parse(
                    $reserva->fecha->toDateString() . ' ' . $reserva->bloque->hora_fin,
                    config('app.timezone')
                );

                if ($this->estaDentroDeVentana($ahora, $fin, 15)
                    && $this->enviarUnaVez($reserva, 'recordatorio_fin_enviado_at', RecordatorioFinReserva::class)) {
                    $finesEnviados++;
                }
            }
        }

        $this->info("Inicio: {$iniciosEnviados} enviados, Fin: {$finesEnviados} enviados");

        return self::SUCCESS;
    }

    private function estaDentroDeVentana(Carbon $ahora, Carbon $objetivo, int $minutosMaximos): bool
    {
        return $objetivo->greaterThanOrEqualTo($ahora->copy()->addMinute())
            && $objetivo->lessThanOrEqualTo($ahora->copy()->addMinutes($minutosMaximos));
    }

    /**
     * Claim the reminder atomically so concurrent scheduler runs cannot send it twice.
     *
     * @param  class-string<RecordatorioInicioReserva|RecordatorioFinReserva>  $notificacion
     */
    private function enviarUnaVez(Reserva $reserva, string $columna, string $notificacion): bool
    {
        $marca = Carbon::now(config('app.timezone'));
        $actualizadas = Reserva::query()
            ->whereKey($reserva->getKey())
            ->whereNull($columna)
            ->update([$columna => $marca]);

        if ($actualizadas !== 1) {
            return false;
        }

        try {
            $reserva->user->notify(new $notificacion($reserva));

            return true;
        } catch (Throwable $exception) {
            Reserva::query()
                ->whereKey($reserva->getKey())
                ->where($columna, $marca)
                ->update([$columna => null]);

            Log::warning('No se pudo enviar un recordatorio de reserva.', [
                'reserva_id' => $reserva->getKey(),
                'tipo' => $columna === 'recordatorio_inicio_enviado_at' ? 'inicio' : 'fin',
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
