<?php

namespace App\Http\Controllers;

use App\Models\BloqueHorario;
use App\Models\Computador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

class DisponibilidadController extends Controller
{
    public function index(Request $request)
    {
        $bloques = BloqueHorario::query()
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->get();

        [$fechaInicio, $fechaFin] = $this->defaultRange($bloques);
        $validationErrors = new MessageBag;
        $bloqueSeleccionado = null;
        $computadores = collect();

        $hasRange = $request->query->has('fecha_inicio') || $request->query->has('fecha_fin');

        if ($hasRange) {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');
            $fechaInicio = is_string($fechaInicio) ? $fechaInicio : '';
            $fechaFin = is_string($fechaFin) ? $fechaFin : '';

            $validator = Validator::make($request->query(), [
                'fecha_inicio' => ['required', 'date_format:Y-m-d\TH:i'],
                'fecha_fin' => ['required', 'date_format:Y-m-d\TH:i', 'after:fecha_inicio'],
            ], [
                'fecha_inicio.required' => 'Indica la fecha y hora de inicio.',
                'fecha_inicio.date_format' => 'La fecha de inicio no es válida.',
                'fecha_fin.required' => 'Indica la fecha y hora de finalización.',
                'fecha_fin.date_format' => 'La fecha de finalización no es válida.',
                'fecha_fin.after' => 'La finalización debe ser posterior al inicio.',
            ]);

            if ($validator->fails()) {
                $validationErrors = $validator->errors();
            } else {
                $inicio = Carbon::createFromFormat('Y-m-d\TH:i', $fechaInicio);
                $fin = Carbon::createFromFormat('Y-m-d\TH:i', $fechaFin);

                if ($inicio->toDateString() !== $fin->toDateString()) {
                    $validationErrors->add('fecha_fin', 'Selecciona un horario dentro del mismo día.');
                } elseif ($inicio->isPast()) {
                    $validationErrors->add('fecha_inicio', 'Selecciona un horario futuro.');
                } elseif ($inicio->copy()->startOfDay()->gt(today()->addDays((int) config('reservas.dias_anticipacion', 7)))) {
                    $validationErrors->add('fecha_inicio', 'El horario supera el límite de anticipación permitido para reservar.');
                } else {
                    $bloqueSeleccionado = $this->findMatchingBlock($bloques, $inicio, $fin);

                    if (! $bloqueSeleccionado) {
                        $validationErrors->add(
                            'fecha_fin',
                            'El rango debe coincidir con uno de los bloques horarios activos.'
                        );
                    }
                }
            }
        } elseif ($fechaInicio && $fechaFin) {
            $inicio = Carbon::createFromFormat('Y-m-d\TH:i', $fechaInicio);
            $fin = Carbon::createFromFormat('Y-m-d\TH:i', $fechaFin);
            $bloqueSeleccionado = $this->findMatchingBlock($bloques, $inicio, $fin);
        }

        if ($validationErrors->isEmpty() && $bloqueSeleccionado && $fechaInicio && $fechaFin) {
            $inicio = Carbon::createFromFormat('Y-m-d\TH:i', $fechaInicio);
            $fin = Carbon::createFromFormat('Y-m-d\TH:i', $fechaFin);
            $fecha = $inicio->toDateString();
            $horaInicio = $inicio->format('H:i:s');
            $horaFin = $fin->format('H:i:s');

            $computadores = Computador::query()
                ->with('aula')
                ->where('estado', 'disponible')
                ->whereHas('aula', fn (Builder $query) => $query->where('estado', 'activo'))
                ->whereDoesntHave('reservas', function (Builder $query) use ($fecha, $horaInicio, $horaFin) {
                    $query->activas()
                        ->whereDate('fecha', $fecha)
                        ->whereHas('bloque', fn (Builder $bloques) => $bloques
                            ->where('hora_inicio', '<', $horaFin)
                            ->where('hora_fin', '>', $horaInicio));
                })
                ->whereDoesntHave('aula.reservas', function (Builder $query) use ($fecha, $horaInicio, $horaFin) {
                    $query->activas()
                        ->whereDate('fecha', $fecha)
                        ->whereHas('bloque', fn (Builder $bloques) => $bloques
                            ->where('hora_inicio', '<', $horaFin)
                            ->where('hora_fin', '>', $horaInicio));
                })
                ->orderBy('marca')
                ->orderBy('modelo')
                ->get();
        }

        return view('disponibilidad.index', compact(
            'bloques',
            'bloqueSeleccionado',
            'computadores',
            'fechaInicio',
            'fechaFin',
            'validationErrors'
        ));
    }

    private function defaultRange(Collection $bloques): array
    {
        if ($bloques->isEmpty()) {
            return [null, null];
        }

        $fecha = today();
        $bloque = $bloques->first(
            fn (BloqueHorario $bloque) => $bloque->inicioEn($fecha->toDateString())->isFuture()
        );

        if (! $bloque) {
            $fecha->addDay();
            $bloque = $bloques->first();
        }

        return [
            $bloque->inicioEn($fecha->toDateString())->format('Y-m-d\TH:i'),
            Carbon::parse($fecha->toDateString().' '.$bloque->hora_fin)->format('Y-m-d\TH:i'),
        ];
    }

    private function findMatchingBlock(Collection $bloques, Carbon $inicio, Carbon $fin): ?BloqueHorario
    {
        return $bloques->first(fn (BloqueHorario $bloque) => substr((string) $bloque->hora_inicio, 0, 5) === $inicio->format('H:i')
            && substr((string) $bloque->hora_fin, 0, 5) === $fin->format('H:i')
        );
    }
}
