<?php

namespace App\Services;

use App\Models\Aula;
use App\Models\Computador;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EstadisticasService
{
    private const ESTADOS_OCUPADOS = ['pendiente', 'entregada', 'devuelta'];

    public function kpis(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null, ?string $estado = null): array
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->endOfDay();
        $desdeKey = $desde->toDateString();
        $hastaKey = $hasta->toDateString();
        $bloqueKey = $bloque ?: 'todos';
        $estadoKey = $estado ?: 'todos';
        $cacheKey = "src.dashboard.kpis:{$desdeKey}:{$hastaKey}:{$aulaId}:{$bloqueKey}:{$estadoKey}";

        return Cache::remember($cacheKey, 60, function () use ($desde, $hasta, $desdeKey, $hastaKey, $aulaId, $bloque, $estado) {
            $dias = (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;

            $equiposDisponiblesQuery = Computador::query()->where('computadores.estado', '!=', 'inactivo');
            $this->aplicarFiltroEquipo($equiposDisponiblesQuery, $aulaId, $bloque);
            $equiposDisponibles = (clone $equiposDisponiblesQuery)->count();

            $bloquesActivosQuery = DB::table('bloques_horarios')->where('activo', true);
            $bloquesActivos = $bloquesActivosQuery->count();

            // Formula documentada: ocupacion (%) =
            // (slots reservados) / (equipos disponibles * bloques horarios activos * dias del rango) * 100.
            // Limite de detalleOcupacion(): 100 registros.
            // Una reserva de aula suma 1 slot por cada equipo no inactivo de esa aula.
            $slotsDisponibles = $equiposDisponibles * $bloquesActivos * $dias;
            $slotsReservados = $this->contarSlotsReservados($desde, $hasta, $aulaId, $bloque, $estado);
            $ocupacion = $slotsDisponibles > 0
                ? round(($slotsReservados / $slotsDisponibles) * 100, 2)
                : 0.0;

            $mesActualInicio = now()->startOfMonth();
            $mesActualFin = now()->copy()->endOfMonth();
            $mesAnteriorInicio = now()->subMonth()->startOfMonth();
            $mesAnteriorFin = $mesAnteriorInicio->copy()->endOfMonth();

            $reservasMesActualQuery = Reserva::query()
                ->whereDate('fecha', '>=', $mesActualInicio->toDateString())
                ->whereDate('fecha', '<=', $mesActualFin->toDateString());
            $this->aplicarFiltroReserva($reservasMesActualQuery, $aulaId, $bloque);
            if ($estado !== null) {
                $reservasMesActualQuery->where('estado', $estado);
            }
            $reservasMes = (clone $reservasMesActualQuery)->count();

            $reservasMesAnteriorQuery = Reserva::query()
                ->whereDate('fecha', '>=', $mesAnteriorInicio->toDateString())
                ->whereDate('fecha', '<=', $mesAnteriorFin->toDateString());
            $this->aplicarFiltroReserva($reservasMesAnteriorQuery, $aulaId, $bloque);
            if ($estado !== null) {
                $reservasMesAnteriorQuery->where('estado', $estado);
            }
            $reservasMesAnterior = (clone $reservasMesAnteriorQuery)->count();

            $variacionMes = $reservasMesAnterior > 0
                ? round((($reservasMes - $reservasMesAnterior) / $reservasMesAnterior) * 100, 2)
                : ($reservasMes > 0 ? 100.0 : 0.0);

            $equiposHoyQuery = Computador::query();
            $this->aplicarFiltroEquipo($equiposHoyQuery, $aulaId, $bloque);
            $equiposHoy = [
                'disponibles' => (clone $equiposHoyQuery)->where('computadores.estado', 'disponible')->count(),
                'en_uso' => (clone $equiposHoyQuery)->where('computadores.estado', 'prestado')->count(),
                'mantenimiento' => (clone $equiposHoyQuery)->where('computadores.estado', 'mantenimiento')->count(),
                'inactivos' => (clone $equiposHoyQuery)->where('computadores.estado', 'inactivo')->count(),
            ];

            $reservasRangoQuery = Reserva::query()
                ->whereDate('fecha', '>=', $desdeKey)
                ->whereDate('fecha', '<=', $hastaKey);
            $this->aplicarFiltroReserva($reservasRangoQuery, $aulaId, $bloque);
            if ($estado !== null) {
                $reservasRangoQuery->where('estado', $estado);
            }
            $reservasRango = (clone $reservasRangoQuery)->count();
            $canceladas = (clone $reservasRangoQuery)->where('estado', 'cancelada')->count();
            $noShow = (clone $reservasRangoQuery)->where('estado', 'no_show')->count();

            $tasaCancelacion = $reservasRango > 0
                ? round(($canceladas / $reservasRango) * 100, 2)
                : 0.0;
            $tasaNoShow = $reservasRango > 0
                ? round(($noShow / $reservasRango) * 100, 2)
                : 0.0;

            return [
                'ocupacion' => $ocupacion,
                'slots_reservados' => $slotsReservados,
                'slots_disponibles' => $slotsDisponibles,
                'equipos_disponibles_para_slots' => $equiposDisponibles,
                'bloques_activos' => $bloquesActivos,
                'dias_rango' => $dias,
                'reservas_mes' => [
                    'total' => $reservasMes,
                    'mes_anterior' => $reservasMesAnterior,
                    'variacion_porcentaje' => $variacionMes,
                ],
                'equipos_hoy' => $equiposHoy,
                'cancelaciones' => [
                    'total_canceladas' => $canceladas,
                    'total_no_show' => $noShow,
                    'tasa_cancelacion' => $tasaCancelacion,
                    'tasa_no_show' => $tasaNoShow,
                    'reservas_rango' => $reservasRango,
                ],
            ];
        });
    }

    public function tendenciaReservas(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null): array
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->startOfDay();

        $query = Reserva::query()
            ->selectRaw('fecha, COUNT(*) as total')
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString())
            ->groupBy('fecha')
            ->orderBy('fecha');
        $this->aplicarFiltroReserva($query, $aulaId, $bloque);

        $totales = $query->get()->mapWithKeys(fn ($fila) => [
            Carbon::parse($fila->fecha)->toDateString() => (int) $fila->total,
        ])->all();
        $dias = (int) $desde->diffInDays($hasta) + 1;

        if ($dias <= 31) {
            $series = [];
            $cursor = $desde->copy();
            while ($cursor->lte($hasta)) {
                $fecha = $cursor->toDateString();
                $series[] = ['fecha' => $fecha, 'total' => $totales[$fecha] ?? 0];
                $cursor->addDay();
            }

            return $series;
        }

        $series = [];
        $cursor = $desde->copy();
        while ($cursor->lte($hasta)) {
            $finSemana = $cursor->copy()->addDays(6);
            if ($finSemana->gt($hasta)) {
                $finSemana = $hasta->copy();
            }

            $total = 0;
            $iterador = $cursor->copy();
            while ($iterador->lte($finSemana)) {
                $total += $totales[$iterador->toDateString()] ?? 0;
                $iterador->addDay();
            }

            $series[] = [
                'fecha_inicio' => $cursor->toDateString(),
                'fecha_fin' => $finSemana->toDateString(),
                'total' => $total,
            ];
            $cursor->addDays(7);
        }

        return $series;
    }

    public function usoPorAula(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null): array
    {
        $desdeFecha = $desde->toDateString();
        $hastaFecha = $hasta->toDateString();

        $reservasAula = Reserva::query()
            ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')
            ->selectRaw('aulas.id as aula_id, aulas.nombre, COUNT(*) as total')
            ->whereNotNull('reservas.aula_id')
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->groupBy('aulas.id', 'aulas.nombre');

        $reservasComputador = Reserva::query()
            ->join('computadores', 'computadores.id', '=', 'reservas.computador_id')
            ->join('aulas', 'aulas.id', '=', 'computadores.aula_id')
            ->selectRaw('aulas.id as aula_id, aulas.nombre, COUNT(*) as total')
            ->whereNull('reservas.aula_id')
            ->whereNotNull('reservas.computador_id')
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->groupBy('aulas.id', 'aulas.nombre');

        $this->aplicarFiltroReservaJoin($reservasAula, $aulaId, $bloque, 'aulas', 'aulas');
        $this->aplicarFiltroReservaJoin($reservasComputador, $aulaId, $bloque, 'computadores', 'aulas');

        $uso = [];
        foreach (array_merge($reservasAula->get()->all(), $reservasComputador->get()->all()) as $fila) {
            $id = (int) $fila->aula_id;
            if (! isset($uso[$id])) {
                $uso[$id] = ['aula_id' => $id, 'aula' => $fila->nombre, 'total' => 0];
            }
            $uso[$id]['total'] += (int) $fila->total;
        }

        $uso = array_values($uso);
        usort($uso, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $uso;
    }

    public function ocupacionPorBloqueHorario(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null): array
    {
        $desdeFecha = $desde->toDateString();
        $hastaFecha = $hasta->toDateString();

        $computadorQuery = Reserva::query()
            ->join('computadores', 'computadores.id', '=', 'reservas.computador_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->leftJoin('aulas', 'aulas.id', '=', 'computadores.aula_id')
            ->selectRaw('bloques_horarios.id as bloque_horario_id, bloques_horarios.nombre, COUNT(*) as total')
            ->whereNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', self::ESTADOS_OCUPADOS)
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true)
            ->groupBy('bloques_horarios.id', 'bloques_horarios.nombre');

        $aulaQuery = Reserva::query()
            ->join('computadores', 'computadores.aula_id', '=', 'reservas.aula_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')
            ->selectRaw('bloques_horarios.id as bloque_horario_id, bloques_horarios.nombre, COUNT(*) as total')
            ->whereNotNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', self::ESTADOS_OCUPADOS)
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true)
            ->groupBy('bloques_horarios.id', 'bloques_horarios.nombre');

        if ($aulaId) {
            $computadorQuery->where('computadores.aula_id', $aulaId);
            $aulaQuery->where('reservas.aula_id', $aulaId);
        }
        if ($bloque !== null && $bloque !== '') {
            $computadorQuery->where(function ($query) use ($bloque) {
                $query->where('aulas.bloque', $bloque)
                    ->orWhere('computadores.bloque', $bloque);
            });
            $aulaQuery->where('aulas.bloque', $bloque);
        }

        $ocupacion = [];
        foreach (array_merge($computadorQuery->get()->all(), $aulaQuery->get()->all()) as $fila) {
            $id = (int) $fila->bloque_horario_id;
            if (! isset($ocupacion[$id])) {
                $ocupacion[$id] = [
                    'bloque_horario_id' => $id,
                    'bloque' => $fila->nombre,
                    'total' => 0,
                ];
            }
            $ocupacion[$id]['total'] += (int) $fila->total;
        }

        $ocupacion = array_values($ocupacion);
        usort($ocupacion, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $ocupacion;
    }

    public function topUsuarios(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null): array
    {
        $query = Reserva::query()
            ->join('users', 'users.id', '=', 'reservas.user_id')
            ->selectRaw('users.id as user_id, users.name, COUNT(*) as total')
            ->whereDate('reservas.fecha', '>=', $desde->toDateString())
            ->whereDate('reservas.fecha', '<=', $hasta->toDateString())
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10);

        $this->aplicarFiltroReserva($query, $aulaId, $bloque);

        return $query->get()
            ->map(fn ($fila) => [
                'user_id' => (int) $fila->user_id,
                'name' => $fila->name,
                'total' => (int) $fila->total,
            ])
            ->all();
    }

    public function detalleOcupacion(Carbon $desde, Carbon $hasta, ?int $aulaId = null, ?string $bloque = null, ?string $estado = null): array
    {
        $desdeFecha = $desde->toDateString();
        $hastaFecha = $hasta->toDateString();

        $directas = Reserva::query()
            ->join('computadores', 'computadores.id', '=', 'reservas.computador_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->join('aulas', 'aulas.id', '=', 'computadores.aula_id')
            ->selectRaw('reservas.fecha, bloques_horarios.id as bloque_horario_id, bloques_horarios.nombre as bloque, aulas.id as aula_id, aulas.nombre as aula, COUNT(*) as reservados')
            ->whereNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', $this->estadosOcupacionFiltrados($estado))
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true)
            ->groupBy('reservas.fecha', 'bloques_horarios.id', 'bloques_horarios.nombre', 'aulas.id', 'aulas.nombre');

        $porAula = Reserva::query()
            ->join('computadores', 'computadores.aula_id', '=', 'reservas.aula_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')
            ->selectRaw('reservas.fecha, bloques_horarios.id as bloque_horario_id, bloques_horarios.nombre as bloque, aulas.id as aula_id, aulas.nombre as aula, COUNT(*) as reservados')
            ->whereNotNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', $this->estadosOcupacionFiltrados($estado))
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true)
            ->groupBy('reservas.fecha', 'bloques_horarios.id', 'bloques_horarios.nombre', 'aulas.id', 'aulas.nombre');

        if ($aulaId) {
            $directas->where('computadores.aula_id', $aulaId);
            $porAula->where('reservas.aula_id', $aulaId);
        }
        if ($bloque !== null && $bloque !== '') {
            $directas->where(function ($query) use ($bloque) {
                $query->where('aulas.bloque', $bloque)
                    ->orWhere('computadores.bloque', $bloque);
            });
            $porAula->where('aulas.bloque', $bloque);
        }

        $equiposQuery = Computador::query()
            ->selectRaw('computadores.aula_id, COUNT(*) as total')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereNotNull('computadores.aula_id')
            ->groupBy('computadores.aula_id');
        $this->aplicarFiltroEquipo($equiposQuery, $aulaId, $bloque);
        $equiposPorAula = $equiposQuery->pluck('total', 'aula_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $detalle = [];
        foreach (array_merge($directas->get()->all(), $porAula->get()->all()) as $fila) {
            $fecha = Carbon::parse($fila->fecha)->toDateString();
            $key = implode('|', [$fecha, $fila->bloque_horario_id, $fila->aula_id]);
            if (! isset($detalle[$key])) {
                $detalle[$key] = [
                    'fecha' => $fecha,
                    'bloque_horario_id' => (int) $fila->bloque_horario_id,
                    'bloque' => $fila->bloque,
                    'aula_id' => (int) $fila->aula_id,
                    'aula' => $fila->aula,
                    'slots_reservados' => 0,
                    'slots_disponibles' => (int) ($equiposPorAula[$fila->aula_id] ?? 0),
                ];
            }
            $detalle[$key]['slots_reservados'] += (int) $fila->reservados;
        }

        $detalle = array_values($detalle);
        usort($detalle, fn ($a, $b) => strcmp($b['fecha'], $a['fecha']) ?: strcmp($a['bloque'], $b['bloque']));

        // El detalle se limita a 100 registros para evitar respuestas/reportes excesivamente grandes.
        return array_slice($detalle, 0, 100);
    }

    private function contarSlotsReservados(Carbon $desde, Carbon $hasta, ?int $aulaId, ?string $bloque, ?string $estado = null): int
    {
        $desdeFecha = $desde->toDateString();
        $hastaFecha = $hasta->toDateString();

        $directas = Reserva::query()
            ->join('computadores', 'computadores.id', '=', 'reservas.computador_id')
            ->leftJoin('aulas', 'aulas.id', '=', 'computadores.aula_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->whereNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', $this->estadosOcupacionFiltrados($estado))
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true);

        $porAula = Reserva::query()
            ->join('computadores', 'computadores.aula_id', '=', 'reservas.aula_id')
            ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')
            ->join('bloques_horarios', 'bloques_horarios.id', '=', 'reservas.bloque_horario_id')
            ->whereNotNull('reservas.aula_id')
            ->where('computadores.estado', '!=', 'inactivo')
            ->whereIn('reservas.estado', $this->estadosOcupacionFiltrados($estado))
            ->whereDate('reservas.fecha', '>=', $desdeFecha)
            ->whereDate('reservas.fecha', '<=', $hastaFecha)
            ->where('bloques_horarios.activo', true);

        if ($aulaId) {
            $directas->where('computadores.aula_id', $aulaId);
            $porAula->where('reservas.aula_id', $aulaId);
        }

        if ($bloque !== null && $bloque !== '') {
            $directas->where(function ($query) use ($bloque) {
                $query->where('aulas.bloque', $bloque)
                    ->orWhere('computadores.bloque', $bloque);
            });
            $porAula->where('aulas.bloque', $bloque);
        }

        return (int) $directas->count() + (int) $porAula->count();
    }

    private function estadosOcupacionFiltrados(?string $estado): array
    {
        if ($estado === null) {
            return self::ESTADOS_OCUPADOS;
        }

        return in_array($estado, self::ESTADOS_OCUPADOS, true) ? [$estado] : [];
    }

    private function aplicarFiltroEquipo(Builder $query, ?int $aulaId, ?string $bloque): void
    {
        if ($aulaId) {
            $query->where('computadores.aula_id', $aulaId);
        }

        if ($bloque !== null && $bloque !== '') {
            $query->leftJoin('aulas', 'aulas.id', '=', 'computadores.aula_id')
                ->where(function ($query) use ($bloque) {
                    $query->where('aulas.bloque', $bloque)
                        ->orWhere('computadores.bloque', $bloque);
                });
        }
    }

    private function aplicarFiltroReserva(Builder $query, ?int $aulaId, ?string $bloque): void
    {
        if ($aulaId) {
            $query->where(function ($query) use ($aulaId) {
                $query->where('reservas.aula_id', $aulaId)
                    ->orWhereHas('computador', fn (Builder $computador) => $computador->where('aula_id', $aulaId));
            });
        }

        if ($bloque !== null && $bloque !== '') {
            $query->where(function ($query) use ($bloque) {
                $query->whereHas('aula', fn (Builder $aula) => $aula->where('bloque', $bloque))
                    ->orWhereHas('computador.aula', fn (Builder $aula) => $aula->where('bloque', $bloque))
                    ->orWhereHas('computador', fn (Builder $computador) => $computador->where('bloque', $bloque));
            });
        }
    }

    private function aplicarFiltroReservaJoin(
        Builder $query,
        ?int $aulaId,
        ?string $bloque,
        string $computadorAlias,
        string $aulaAlias
    ): void {
        if ($aulaId) {
            if ($query->getQuery()->joins) {
                $query->where(function ($query) use ($aulaId, $computadorAlias) {
                    $query->where('reservas.aula_id', $aulaId);
                    if ($computadorAlias === 'computadores') {
                        $query->orWhere('computadores.aula_id', $aulaId);
                    }
                });
            } else {
                $query->where('reservas.aula_id', $aulaId);
            }
        }

        if ($bloque !== null && $bloque !== '') {
            $query->where($aulaAlias.'.bloque', $bloque);
        }
    }
}
