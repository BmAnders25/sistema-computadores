<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

abstract class BasePruebaSeeder extends Seeder
{
    protected function protegerEntorno(): void
    {
        abort_unless(app()->environment('local'), 403);
    }

    protected function usuario(string $email, string $nombre, string $rol): User
    {
        $role = Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
        $esEstudiante = $rol === 'Estudiante';
        $codigo = $esEstudiante
            ? 'PRUEBA' . strtoupper(substr(hash('sha256', $email), 0, 12))
            : null;

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nombre,
                'password' => Hash::make('Password123'),
                'estado' => 'activo',
                'two_factor_enabled' => false,
                'codigo_institucional' => $codigo,
                'programa' => $esEstudiante ? 'Ingeniería de Sistemas' : null,
            ]
        );
        $user->forceFill([
            'email_verified_at' => Carbon::now(config('app.timezone')),
        ])->save();
        $user->syncRoles([$role]);

        return $user;
    }

    protected function bloque(string $nombre, Carbon $inicio, Carbon $fin): BloqueHorario
    {
        return BloqueHorario::updateOrCreate(
            ['nombre' => $nombre],
            [
                'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fin' => $fin->format('H:i:s'),
                'activo' => true,
            ]
        );
    }

    protected function equipoLibre(
        User $user,
        string $fecha,
        BloqueHorario $bloque,
        array $excepto = []
    ): ?Computador {
        $computadores = Computador::query()
            ->with('aula')
            ->whereNotIn('id', $excepto)
            ->orderBy('id')
            ->get();

        foreach ($computadores as $computador) {
            $claveEquipo = Reserva::claveEquipo($computador->id, $fecha, $bloque->id);
            $claveAula = Reserva::claveAula($computador->aula_id, $fecha, $bloque->id);
            $ocupado = Reserva::query()
                ->where(function ($query) use ($claveEquipo, $claveAula) {
                    $query->where('clave_unica', $claveEquipo)
                        ->orWhere('clave_unica', $claveAula);
                })
                ->where('user_id', '!=', $user->id)
                ->exists();

            if (! $ocupado) {
                return $computador;
            }
        }

        return null;
    }

    protected function aulaLibre(User $user, Aula $aula, string $fecha, BloqueHorario $bloque): bool
    {
        $clave = Reserva::claveAula($aula->id, $fecha, $bloque->id);
        $ocupada = Reserva::query()
            ->where('clave_unica', $clave)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($ocupada) {
            return false;
        }

        return ! Reserva::query()
            ->whereHas('computador', fn ($query) => $query->where('aula_id', $aula->id))
            ->where('fecha', $fecha)
            ->where('bloque_horario_id', $bloque->id)
            ->whereIn('estado', ['pendiente', 'entregada'])
            ->where('user_id', '!=', $user->id)
            ->exists();
    }

    protected function guardarReserva(
        User $user,
        BloqueHorario $bloque,
        string $fecha,
        string $estado,
        ?Computador $computador = null,
        ?Aula $aula = null,
        ?Carbon $entregadoAt = null,
        ?Carbon $devueltoAt = null
    ): ?Reserva {
        if (! in_array($estado, ['pendiente', 'entregada', 'devuelta', 'cancelada', 'no_show'], true)) {
            throw new \InvalidArgumentException("Estado de prueba no permitido: {$estado}");
        }

        $clave = match (true) {
            in_array($estado, ['pendiente', 'entregada'], true) && $computador !== null =>
                Reserva::claveEquipo($computador->id, $fecha, $bloque->id),
            in_array($estado, ['pendiente', 'entregada'], true) && $aula !== null =>
                Reserva::claveAula($aula->id, $fecha, $bloque->id),
            default => null,
        };

        $existente = Reserva::query()
            ->where('user_id', $user->id)
            ->where('bloque_horario_id', $bloque->id)
            ->whereDate('fecha', $fecha)
            ->first();

        if ($clave !== null && Reserva::query()
            ->where('clave_unica', $clave)
            ->when($existente, fn ($query) => $query->where('id', '!=', $existente->getKey()))
            ->exists()) {
            $this->command?->warn("Slot ocupado; se omite {$user->email} / {$bloque->nombre}.");

            return null;
        }

        $datos = [
            'user_id' => $user->id,
            'computador_id' => $computador?->id,
            'aula_id' => $aula?->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => $fecha,
            'estado' => $estado,
            'entregado_at' => $entregadoAt,
            'devuelto_at' => $devueltoAt,
            'clave_unica' => $clave,
        ];

        if ($existente) {
            $existente->update($datos);
            $reserva = $existente->fresh();
        } else {
            $reserva = Reserva::create($datos);
        }

        Reserva::query()
            ->whereKey($reserva->getKey())
            ->update([
                'recordatorio_inicio_enviado_at' => null,
                'recordatorio_fin_enviado_at' => null,
            ]);

        return $reserva;
    }

    protected function resumen(User $user, string $rol, string $escenario, ?Reserva $reserva): void
    {
        if ($this->command) {
            $this->command->table(
                ['Correo', 'Rol', 'Escenario', 'Estado de reserva', 'Contraseña'],
                [[
                    $user->email,
                    $rol,
                    $escenario,
                    $reserva?->estado_etiqueta ?? 'No creada (slot ocupado)',
                    'Password123',
                ]]
            );
        }
    }
}
