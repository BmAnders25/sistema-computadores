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
                'desde' => today()->subDays(367)->toDateString(),
                'hasta' => today()->toDateString(),
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
            'nombre' => $inicio . ' - ' . $fin,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'activo' => true,
        ]);
    }
}
