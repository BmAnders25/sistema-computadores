<?php

namespace Database\Seeders;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class NotificacionesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = Carbon::now(config('app.timezone'));
        $hoy = $ahora->toDateString();
        $escenarios = [
            [
                'email' => 'prueba.inicio@unipamplona.edu.co',
                'nombre' => 'Prueba Recordatorio Inicio',
                'bloque' => 'Prueba notif inicio',
                'inicio' => $ahora->copy()->addMinutes(20),
                'fin' => $ahora->copy()->addMinutes(80),
                'estado' => 'pendiente',
            ],
            [
                'email' => 'prueba.fin@unipamplona.edu.co',
                'nombre' => 'Prueba Recordatorio Fin',
                'bloque' => 'Prueba notif fin',
                'inicio' => $ahora->copy()->subMinutes(50),
                'fin' => $ahora->copy()->addMinutes(10),
                'estado' => 'entregada',
            ],
            [
                'email' => 'prueba.lejana@unipamplona.edu.co',
                'nombre' => 'Prueba Reserva Lejana',
                'bloque' => 'Prueba notif lejana',
                'inicio' => $ahora->copy()->addHours(3),
                'fin' => $ahora->copy()->addHours(4),
                'estado' => 'pendiente',
            ],
            [
                'email' => 'prueba.devuelta@unipamplona.edu.co',
                'nombre' => 'Prueba Reserva Devuelta',
                'bloque' => 'Prueba notif devuelta',
                'inicio' => $ahora->copy()->subMinutes(50),
                'fin' => $ahora->copy()->addMinutes(10),
                'estado' => 'devuelta',
            ],
        ];

        foreach ($escenarios as $escenario) {
            if ($escenario['inicio']->toDateString() !== $hoy || $escenario['fin']->toDateString() !== $hoy) {
                throw new RuntimeException(
                    'Este seeder requiere que los cuatro horarios de prueba queden dentro del día actual. Ejecútalo antes de las 21:00 en America/Bogota.'
                );
            }
        }

        $rolEstudiante = Role::firstOrCreate([
            'name' => 'Estudiante',
            'guard_name' => 'web',
        ]);
        $aula = Aula::updateOrCreate(
            ['bloque' => 'Pruebas', 'nombre' => 'LAB-NOTIF'],
            ['capacidad' => 4, 'estado' => 'activo'],
        );

        $credenciales = [];

        foreach ($escenarios as $escenario) {
            $usuario = User::updateOrCreate(
                ['email' => $escenario['email']],
                [
                    'name' => $escenario['nombre'],
                    'password' => Hash::make('password'),
                    'estado' => 'activo',
                ],
            );
            $usuario->syncRoles([$rolEstudiante]);

            $bloque = BloqueHorario::updateOrCreate(
                ['nombre' => $escenario['bloque']],
                [
                    'hora_inicio' => $escenario['inicio']->format('H:i:s'),
                    'hora_fin' => $escenario['fin']->format('H:i:s'),
                    'activo' => true,
                ],
            );

            $claveActiva = in_array($escenario['estado'], Reserva::ACTIVAS, true)
                ? Reserva::claveAula($aula->id, $hoy, $bloque->id)
                : null;
            $entregadoAt = in_array($escenario['estado'], ['entregada', 'devuelta'], true)
                ? $escenario['inicio']->copy()->addMinutes(5)
                : null;
            $devueltoAt = $escenario['estado'] === 'devuelta'
                ? $ahora->copy()->subMinutes(5)
                : null;

            $reserva = Reserva::updateOrCreate(
                ['user_id' => $usuario->id],
                [
                    'computador_id' => null,
                    'aula_id' => $aula->id,
                    'bloque_horario_id' => $bloque->id,
                    'fecha' => $hoy,
                    'estado' => $escenario['estado'],
                    'entregado_at' => $entregadoAt,
                    'devuelto_at' => $devueltoAt,
                    'clave_unica' => $claveActiva,
                ],
            );
            $reserva->newQuery()
                ->whereKey($reserva->getKey())
                ->update([
                    'recordatorio_inicio_enviado_at' => null,
                    'recordatorio_fin_enviado_at' => null,
                ]);

            $credenciales[] = [
                'Correo' => $escenario['email'],
                'Contraseña' => 'password',
            ];
        }

        if ($this->command) {
            $this->command->table(['Correo', 'Contraseña'], $credenciales);
            $this->command->warn('El seeder ya creó las reservas de prueba; no registres otra reserva con esas cuentas.');
            $this->command->line('Para probar los recordatorios ahora: php artisan reservas:recordatorios');
        }
    }
}
