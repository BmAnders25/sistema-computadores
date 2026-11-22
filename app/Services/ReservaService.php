<?php

namespace App\Services;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaService
{
    public function reservarEquipo(User $user, int $computadorId, string $fecha, int $bloqueId): Reserva
    {
        $fecha  = Carbon::parse($fecha)->toDateString();
        $bloque = BloqueHorario::where('activo', true)->findOrFail($bloqueId);

        $this->validarVentana($fecha, $bloque);
        $this->validarLimiteDiario($user, $fecha);

        return DB::transaction(function () use ($user, $computadorId, $fecha, $bloque) {
            $computador = Computador::findOrFail($computadorId);

            // Se bloquea el aula para serializar reservas concurrentes sobre la misma sala
            $aula = Aula::lockForUpdate()->findOrFail($computador->aula_id);

            if (!in_array(strtolower($aula->estado), ['activa', 'activo'], true)) {
                $this->fallar('El aula de este equipo no está habilitada.');
            }
            if (in_array($computador->estado, ['mantenimiento', 'inactivo'], true)) {
                $this->fallar('Este equipo no está disponible para reservas.');
            }

            // ¿Hay una reserva del aula completa en ese bloque?
            $aulaOcupada = Reserva::activas()
                ->where('aula_id', $aula->id)
                ->where('fecha', $fecha)
                ->where('bloque_horario_id', $bloque->id)
                ->exists();
            if ($aulaOcupada) {
                $this->fallar('El aula completa está reservada en ese horario.');
            }

            $this->validarSinCruce($user, $fecha, $bloque->id);

            return $this->crear($user, $fecha, $bloque->id,
                ['computador_id' => $computador->id],
                Reserva::claveEquipo($computador->id, $fecha, $bloque->id),
                'Ese equipo ya fue reservado en ese horario.'
            );
        });
    }

    /** Solo Docente/Administración (se controla con el permiso en el controlador). */
    public function reservarAula(User $user, int $aulaId, string $fecha, int $bloqueId): Reserva
    {
        $fecha  = Carbon::parse($fecha)->toDateString();
        $bloque = BloqueHorario::where('activo', true)->findOrFail($bloqueId);

        $this->validarVentana($fecha, $bloque);

        return DB::transaction(function () use ($user, $aulaId, $fecha, $bloque) {
            $aula = Aula::lockForUpdate()->findOrFail($aulaId);

            if (!in_array(strtolower($aula->estado), ['activa', 'activo'], true)) {
                $this->fallar('El aula no está habilitada.');
            }

            // Ni el aula ni ninguno de sus equipos pueden estar reservados en ese bloque
            $ocupada = Reserva::activas()
                ->where('fecha', $fecha)
                ->where('bloque_horario_id', $bloque->id)
                ->where(function ($q) use ($aula) {
                    $q->where('aula_id', $aula->id)
                      ->orWhereHas('computador', fn ($c) => $c->where('aula_id', $aula->id));
                })->exists();
            if ($ocupada) {
                $this->fallar('El aula o alguno de sus equipos ya tiene reservas en ese horario.');
            }

            $this->validarSinCruce($user, $fecha, $bloque->id);

            return $this->crear($user, $fecha, $bloque->id,
                ['aula_id' => $aula->id],
                Reserva::claveAula($aula->id, $fecha, $bloque->id),
                'El aula ya fue reservada en ese horario.'
            );
        });
    }

    public function cancelar(User $user, Reserva $reserva): void
    {
        $esAdmin = $user->hasRole('Administracion');

        abort_unless($esAdmin || $reserva->user_id === $user->id, 403);

        if ($reserva->estado !== 'pendiente') {
            $this->fallar('Solo se pueden cancelar reservas pendientes.');
        }

        if (!$esAdmin) {
            $minutosMin = config('reservas.minutos_min_cancelacion', 30);
            $limite = $reserva->bloque
                ->inicioEn($reserva->fecha->toDateString())
                ->subMinutes($minutosMin);

            if (now()->gt($limite)) {
                $this->fallar('Ya no es posible cancelar: el plazo venció.');
            }
        }

        $reserva->liberar('cancelada');
    }

    // ----- Validaciones internas -----

    private function validarVentana(string $fecha, BloqueHorario $bloque): void
    {
        $diasAnticipacion = config('reservas.dias_anticipacion', 7);
        $max = today()->addDays($diasAnticipacion);

        if (Carbon::parse($fecha)->gt($max)) {
            $this->fallar('Solo puedes reservar con ' . $diasAnticipacion . ' días de anticipación.');
        }
        if ($bloque->inicioEn($fecha)->isPast()) {
            $this->fallar('Ese bloque horario ya comenzó o ya pasó.');
        }
    }

    private function validarLimiteDiario(User $user, string $fecha): void
    {
        
        // Valor por defecto de 1 reserva diaria si la configuración no existe
        $maxReservas = config('reservas.max_reservas_dia_estudiante', 1);

        $cantidad = Reserva::activas()->where('user_id', $user->id)->where('fecha', $fecha)->count();

        if ($cantidad >= $maxReservas) {
            $this->fallar('Alcanzaste el máximo de reservas permitidas para ese día.');
        }
    }

    private function validarSinCruce(User $user, string $fecha, int $bloqueId): void
    {
        $cruce = Reserva::activas()
            ->where('user_id', $user->id)
            ->where('fecha', $fecha)
            ->where('bloque_horario_id', $bloqueId)
            ->exists();

        if ($cruce) {
            $this->fallar('Ya tienes una reserva en ese horario.');
        }
    }

    private function crear(User $user, string $fecha, int $bloqueId, array $destino, string $clave, string $msgDuplicado): Reserva
    {
        try {
            return Reserva::create($destino + [
                'user_id'           => $user->id,
                'bloque_horario_id' => $bloqueId,
                'fecha'             => $fecha,
                'estado'            => 'pendiente',
                'clave_unica'       => $clave,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {      // violación del índice único
                $this->fallar($msgDuplicado);
            }
            throw $e;
        }
    }

    private function fallar(string $mensaje): void
    {
        throw ValidationException::withMessages(['reserva' => $mensaje]);
    }
}