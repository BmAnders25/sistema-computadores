<?php

namespace App\Services;

use App\Models\Computador;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntregaService
{
    public function registrarEntrega(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            if ($reserva->estado !== 'confirmada' || ! $reserva->computador_id) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo se pueden registrar entregas de reservas confirmadas de computador.',
                ]);
            }

            $computador = Computador::query()->lockForUpdate()->findOrFail($reserva->computador_id);

            if ($computador->estado !== 'disponible') {
                throw ValidationException::withMessages([
                    'reserva' => 'El computador no está disponible para registrar esta entrega.',
                ]);
            }

            $reserva->update([
                'estado' => 'entregada',
                'entregado_at' => now(),
            ]);
            $computador->update(['estado' => 'prestado']);
        });
    }

    public function registrarDevolucion(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reserva = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            if ($reserva->estado !== 'entregada' || ! $reserva->computador_id) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo se pueden registrar devoluciones de computadores entregados.',
                ]);
            }

            $computador = Computador::query()->lockForUpdate()->findOrFail($reserva->computador_id);

            $reserva->update([
                'estado' => 'devuelta',
                'devuelto_at' => now(),
                'clave_unica' => null,
            ]);

            if ($computador->estado === 'prestado') {
                $computador->update(['estado' => 'disponible']);
            }
        });
    }
}
