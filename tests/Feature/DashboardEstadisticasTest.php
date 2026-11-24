<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use App\Services\EstadisticasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_occupation_is_calculated_from_equipment_blocks_and_days(): void
    {
        Cache::flush();
        $aula = $this->createAula('LAB-EST-1');
        $computadores = [
            $this->createComputador($aula, 'EST-01'),
            $this->createComputador($aula, 'EST-02'),
        ];
        $bloqueUno = $this->createBloque('07:00', '09:00');
        $this->createBloque('09:00', '11:00');
        $usuario = User::factory()->create(['estado' => 'activo']);
        $fecha = today()->toDateString();

        Reserva::create([
            'user_id' => $usuario->id,
            'computador_id' => $computadores[0]->id,
            'bloque_horario_id' => $bloqueUno->id,
            'fecha' => $fecha,
            'estado' => 'pendiente',
            'clave_unica' => 'occupation-direct-25',
        ]);

        Reserva::create([
            'user_id' => $usuario->id,
            'computador_id' => $computadores[1]->id,
            'bloque_horario_id' => $bloqueUno->id,
            'fecha' => $fecha,
            'estado' => 'cancelada',
            'clave_unica' => null,
        ]);

        $desde = Carbon::parse($fecha)->startOfDay();
        $hasta = Carbon::parse($fecha)->endOfDay();
        $kpis = app(EstadisticasService::class)->kpis($desde, $hasta);

        $this->assertSame(4, $kpis['slots_disponibles']);
        $this->assertSame(1, $kpis['slots_reservados']);
        $this->assertSame(25.0, $kpis['ocupacion']);
    }

    public function test_cancelled_reservation_does_not_count_and_aula_reservation_counts_all_equipment(): void
    {
        Cache::flush();
        $aula = $this->createAula('LAB-AULA-COMPLETA');
        $this->createComputador($aula, 'AULA-01');
        $this->createComputador($aula, 'AULA-02');
        $bloque = $this->createBloque('13:00', '15:00');
        $usuario = User::factory()->create(['estado' => 'activo']);
        $fecha = today()->toDateString();

        Reserva::create([
            'user_id' => $usuario->id,
            'aula_id' => $aula->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => $fecha,
            'estado' => 'devuelta',
            'clave_unica' => null,
        ]);

        Reserva::create([
            'user_id' => $usuario->id,
            'aula_id' => $aula->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => $fecha,
            'estado' => 'cancelada',
            'clave_unica' => null,
        ]);

        $desde = Carbon::parse($fecha)->startOfDay();
        $hasta = Carbon::parse($fecha)->endOfDay();
        $kpis = app(EstadisticasService::class)->kpis($desde, $hasta, $aula->id);

        $this->assertSame(2, $kpis['slots_disponibles']);
        $this->assertSame(2, $kpis['slots_reservados']);
        $this->assertSame(100.0, $kpis['ocupacion']);
    }

    public function test_dashboard_data_matches_reservation_states_filters_and_chart_totals(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-23 09:00:00', config('app.timezone')));

        try {
            $permission = Permission::firstOrCreate([
                'name' => 'ver-estadistica',
                'guard_name' => 'web',
            ]);
            $adminRole = Role::firstOrCreate([
                'name' => 'Administracion',
                'guard_name' => 'web',
            ]);
            $adminRole->givePermissionTo($permission);
            $admin = User::factory()->create(['estado' => 'activo']);
            $admin->assignRole($adminRole);
            $usuario = User::factory()->create(['estado' => 'activo']);

            $aulaA = $this->createAula('LAB-DASHBOARD-A');
            $aulaB = $this->createAula('LAB-DASHBOARD-B');
            $aulaB->update(['bloque' => 'Bloque B']);
            $equipoDisponible = $this->createComputador($aulaA, 'DASH-01');
            $equipoEnUso = $this->createComputador($aulaA, 'DASH-02');
            $equipoEnUso->update(['estado' => 'prestado']);
            $equipoInactivo = $this->createComputador($aulaA, 'DASH-03');
            $equipoInactivo->update(['estado' => 'inactivo']);
            $equipoOtroBloque = $this->createComputador($aulaB, 'DASH-04');
            $primerBloque = $this->createBloque('07:00', '09:00');
            $segundoBloque = $this->createBloque('09:00', '11:00');
            $this->createBloque('11:00', '13:00')->update(['activo' => false]);

            $crearReserva = function (
                ?Computador $equipo,
                ?Aula $aula,
                BloqueHorario $bloque,
                string $fecha,
                string $estado
            ) use ($usuario): Reserva {
                $clave = null;
                if (in_array($estado, ['pendiente', 'entregada'], true)) {
                    $clave = $equipo
                        ? Reserva::claveEquipo($equipo->id, $fecha, $bloque->id)
                        : Reserva::claveAula($aula->id, $fecha, $bloque->id);
                }

                return Reserva::create([
                    'user_id' => $usuario->id,
                    'computador_id' => $equipo?->id,
                    'aula_id' => $aula?->id,
                    'bloque_horario_id' => $bloque->id,
                    'fecha' => $fecha,
                    'estado' => $estado,
                    'entregado_at' => in_array($estado, ['entregada', 'devuelta'], true) ? now()->subHour() : null,
                    'devuelto_at' => $estado === 'devuelta' ? now() : null,
                    'clave_unica' => $clave,
                ]);
            };

            $ayer = '2026-11-22';
            $hoy = '2026-11-23';
            $mesAnterior = '2026-10-30';
            $crearReserva($equipoDisponible, null, $primerBloque, $ayer, 'pendiente');
            $crearReserva($equipoEnUso, null, $segundoBloque, $ayer, 'entregada');
            $crearReserva($equipoDisponible, null, $primerBloque, $hoy, 'devuelta');
            $crearReserva($equipoEnUso, null, $segundoBloque, $hoy, 'cancelada');
            $crearReserva($equipoEnUso, null, $primerBloque, $ayer, 'no_show');
            $crearReserva(null, $aulaA, $segundoBloque, $hoy, 'pendiente');
            $crearReserva($equipoOtroBloque, null, $primerBloque, $ayer, 'pendiente');
            $crearReserva($equipoDisponible, null, $primerBloque, $mesAnterior, 'no_show');

            $response = $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->getJson(route('dashboard.datos', [
                    'desde' => $ayer,
                    'hasta' => $hoy,
                ]))
                ->assertOk()
                ->assertJsonPath('kpis.slots_disponibles', 12)
                ->assertJsonPath('kpis.slots_reservados', 6)
                ->assertJsonPath('kpis.ocupacion', 50)
                ->assertJsonPath('kpis.equipos_disponibles_para_slots', 3)
                ->assertJsonPath('kpis.equipos_hoy.disponibles', 2)
                ->assertJsonPath('kpis.equipos_hoy.en_uso', 1)
                ->assertJsonPath('kpis.equipos_hoy.inactivos', 1)
                ->assertJsonPath('kpis.reservas_mes.total', 7)
                ->assertJsonPath('kpis.reservas_mes.mes_anterior', 1)
                ->assertJsonPath('kpis.reservas_mes.variacion_porcentaje', 600)
                ->assertJsonPath('kpis.cancelaciones.reservas_rango', 7)
                ->assertJsonPath('kpis.cancelaciones.total_canceladas', 1)
                ->assertJsonPath('kpis.cancelaciones.total_no_show', 1)
                ->assertJsonPath('kpis.cancelaciones.tasa_cancelacion', 14.29)
                ->assertJsonPath('kpis.cancelaciones.tasa_no_show', 14.29)
                ->assertJsonPath('tendencia.0.total', 4)
                ->assertJsonPath('tendencia.1.total', 3)
                ->assertJsonPath('uso_por_aula.0.total', 6)
                ->assertJsonPath('uso_por_aula.1.total', 1)
                ->assertJsonPath('top_usuarios.0.total', 7)
                ->assertJsonCount(5, 'detalle_ocupacion');

            $ocupacionPorBloque = collect($response->json('ocupacion_por_bloque'))->keyBy('bloque');
            $this->assertSame(3, $ocupacionPorBloque[$segundoBloque->nombre]['total']);
            $this->assertSame(3, $ocupacionPorBloque[$primerBloque->nombre]['total']);

            $detalleAulaCompleta = collect($response->json('detalle_ocupacion'))
                ->first(fn (array $fila) => $fila['fecha'] === $hoy && $fila['bloque'] === $segundoBloque->nombre);
            $this->assertSame(2, $detalleAulaCompleta['slots_reservados']);
            $this->assertSame(2, $detalleAulaCompleta['slots_disponibles']);

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->getJson(route('dashboard.datos', [
                    'desde' => $ayer,
                    'hasta' => $hoy,
                    'aula_id' => $aulaA->id,
                    'bloque' => 'Bloque A',
                ]))
                ->assertOk()
                ->assertJsonPath('kpis.slots_disponibles', 8)
                ->assertJsonPath('kpis.slots_reservados', 5)
                ->assertJsonCount(4, 'detalle_ocupacion');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_data_access_is_restricted_by_role_and_validates_dates(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ver-estadistica',
            'guard_name' => 'web',
        ]);
        $administracion = Role::firstOrCreate([
            'name' => 'Administracion',
            'guard_name' => 'web',
        ]);
        $administracion->givePermissionTo($permission);

        $otherRoles = [
            'Estudiante',
            'Docente',
            'Celador',
        ];
        $roleModels = [];
        foreach ($otherRoles as $roleName) {
            $roleModels[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $admin = User::factory()->create(['estado' => 'activo']);
        $admin->assignRole($administracion);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sin datos')
            ->assertSee('data-chart-empty="dashboardTrendChart"', false);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->subDays(6)->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'kpis',
                'tendencia',
                'uso_por_aula',
                'ocupacion_por_bloque',
                'top_usuarios',
                'detalle_ocupacion',
            ]);

        foreach ($roleModels as $role) {
            $user = User::factory()->create(['estado' => 'activo']);
            $user->assignRole($role);

            $this->actingAs($user)
                ->withSession(['two_factor_passed' => true])
                ->getJson(route('dashboard.datos', [
                    'desde' => today()->subDays(6)->toDateString(),
                    'hasta' => today()->toDateString(),
                ]))
                ->assertForbidden();
        }

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->toDateString(),
                'hasta' => today()->subDay()->toDateString(),
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->subDays(366)->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->subDays(365)->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->toDateString(),
                'hasta' => today()->toDateString(),
                'aula_id' => 999999,
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('dashboard.datos', [
                'desde' => today()->toDateString(),
                'hasta' => today()->toDateString(),
                'bloque' => str_repeat('B', 51),
            ]))
            ->assertStatus(422);
    }

    private function createAula(string $nombre): Aula
    {
        return Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => $nombre,
            'capacidad' => 30,
            'estado' => 'activo',
        ]);
    }

    private function createComputador(Aula $aula, string $serial): Computador
    {
        return Computador::create([
            'serial' => $serial,
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
    }

    private function createBloque(string $inicio, string $fin): BloqueHorario
    {
        return BloqueHorario::create([
            'nombre' => $inicio.' - '.$fin,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'activo' => true,
        ]);
    }
}
