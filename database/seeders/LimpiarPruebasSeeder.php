<?php

namespace Database\Seeders;

use App\Models\BloqueHorario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;

class LimpiarPruebasSeeder extends Seeder
{
    public function run(): void
    {
        abort_unless(app()->environment('local'), 403);

        $usuarios = User::query()->where('email', 'like', 'prueba.%')->get();
        $userIds = $usuarios->modelKeys();
        $bloqueIds = BloqueHorario::query()->where('nombre', 'like', 'PRUEBA-%')->pluck('id');

        if ($userIds !== []) {
            DatabaseNotification::query()
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $userIds)
                ->delete();
        }

        Reserva::query()
            ->whereIn('bloque_horario_id', $bloqueIds)
            ->orWhereIn('user_id', $userIds)
            ->delete();

        User::query()->whereIn('id', $userIds)->delete();
        BloqueHorario::query()->whereIn('id', $bloqueIds)->delete();

        $this->command?->info(sprintf(
            'Limpieza de pruebas completada: %d usuarios y %d bloques eliminados.',
            count($userIds),
            $bloqueIds->count()
        ));
    }
}
