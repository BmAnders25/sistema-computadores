<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Aula;
use App\Models\Computador;
use App\Models\BloqueHorario;
use App\Notifications\ConfirmacionReserva;
use App\Notifications\RecordatorioInicioReserva;
use App\Services\ReservaService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservaController extends Controller
{
    protected ReservaService $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;

        $this->middleware('permission:ver-reservas', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-reservas', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-reservas', ['only' => ['edit', 'update']]);
        $this->middleware('permission:cancelar-reservas', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Si es Administración o Celador, ven todas las reservas; si es Estudiante/Docente, solo las suyas.
        if ($user->hasRole(['Administracion', 'Celador'])) {
            $baseQuery = Reserva::query();
        } else {
            $baseQuery = Reserva::query()->where('user_id', $user->id);
        }

        $totalReservas = (clone $baseQuery)->count();
        $pendientesCount = (clone $baseQuery)->where('estado', 'pendiente')->count();
        $confirmadasCount = (clone $baseQuery)->where('estado', 'confirmada')->count();
        $enUsoCount = (clone $baseQuery)->where('estado', 'entregada')->count();
        $historialCount = (clone $baseQuery)->whereIn('estado', ['devuelta', 'cancelada', 'no_show'])->count();

        $query = (clone $baseQuery)->with(['user.roles', 'aula', 'computador.aula', 'bloque']);
        $estado = $request->query('estado');
        if (in_array($estado, ['pendiente', 'confirmada', 'entregada'], true)) {
            $query->where('estado', $estado);
        } elseif ($estado === 'historial') {
            $query->whereIn('estado', ['devuelta', 'cancelada', 'no_show']);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($query) use ($like) {
                $query->whereHas('user', function ($users) use ($like) {
                    $users->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })->orWhereHas('computador', function ($computadores) use ($like) {
                    $computadores->where('marca', 'like', $like)
                        ->orWhere('modelo', 'like', $like)
                        ->orWhere('serial', 'like', $like)
                        ->orWhereHas('aula', function ($aulas) use ($like) {
                            $aulas->where('nombre', 'like', $like)
                                ->orWhere('bloque', 'like', $like);
                        });
                })->orWhereHas('aula', function ($aulas) use ($like) {
                    $aulas->where('nombre', 'like', $like)
                        ->orWhere('bloque', 'like', $like);
                })->orWhereHas('bloque', function ($bloques) use ($like) {
                    $bloques->where('nombre', 'like', $like)
                        ->orWhere('hora_inicio', 'like', $like)
                        ->orWhere('hora_fin', 'like', $like);
                });
            });
        }

        $reservas = $query->with(['user.roles', 'aula', 'computador.aula', 'bloque'])
            ->latest()
            ->paginate($this->catalogPerPage($request))
            ->withQueryString();

        return view('reservas.index', compact(
            'reservas',
            'totalReservas',
            'pendientesCount',
            'confirmadasCount',
            'enUsoCount',
            'historialCount'
        ));
    }

    public function create(Request $request)
    {
        $tipo = $request->get('tipo', 'equipo'); // 'equipo' o 'aula'
        $computadorId = $request->query('computador_id');
        $fecha = $request->query('fecha');
        $bloqueHorarioId = $request->query('bloque_horario_id');
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');
        $aulas = Aula::whereIn('estado', ['activo', 'activa'])->get();
        $computadores = Computador::with('aula')->where('estado', 'disponible')->get();
        $bloques = BloqueHorario::where('activo', true)->get();

        return view('reservas.create', compact(
            'tipo',
            'aulas',
            'computadores',
            'bloques',
            'computadorId',
            'fecha',
            'bloqueHorarioId',
            'fechaInicio',
            'fechaFin'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo'              => 'required|in:equipo,aula',
            'fecha'             => 'required|date|after_or_equal:today',
            'bloque_horario_id' => 'required|exists:bloques_horarios,id',
            'computador_id'     => 'required_if:tipo,equipo|nullable|exists:computadores,id',
            'aula_id'           => 'required_if:tipo,aula|nullable|exists:aulas,id',
        ]);

        $user = Auth::user();

        try {
            if ($request->tipo === 'aula') {
                // Validar que tenga el permiso explícito para reservar aulas completas
                if (!$user->can('reservar-aulas')) {
                    throw ValidationException::withMessages(['reserva' => 'No tienes permisos para reservar aulas completas.']);
                }
                $reserva = $this->reservaService->reservarAula($user, $request->aula_id, $request->fecha, $request->bloque_horario_id);
            } else {
                $reserva = $this->reservaService->reservarEquipo($user, $request->computador_id, $request->fecha, $request->bloque_horario_id);
            }

            $notificacionFallida = false;
            try {
                $user->notify(new ConfirmacionReserva($reserva));
            } catch (Throwable $exception) {
                $notificacionFallida = true;
                Log::warning('La reserva se guardó, pero no se pudo enviar su correo de confirmación.', [
                    'reserva_id' => $reserva->getKey(),
                    'user_id' => $user->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }

            $reserva->loadMissing(['bloque', 'computador', 'aula']);
            $ahora = Carbon::now(config('app.timezone'));
            $inicio = Carbon::parse(
                $reserva->fecha->toDateString() . ' ' . $reserva->bloque->hora_inicio,
                config('app.timezone')
            );

            if ($reserva->estado === 'pendiente'
                && $inicio->greaterThan($ahora)
                && $inicio->lessThan($ahora->copy()->addMinutes(30))) {
                $marca = Carbon::now(config('app.timezone'));
                $actualizadas = Reserva::query()
                    ->whereKey($reserva->getKey())
                    ->whereNull('recordatorio_inicio_enviado_at')
                    ->update(['recordatorio_inicio_enviado_at' => $marca]);

                if ($actualizadas === 1) {
                    try {
                        $user->notify(new RecordatorioInicioReserva($reserva));
                    } catch (Throwable $exception) {
                        $notificacionFallida = true;
                        Reserva::query()
                            ->whereKey($reserva->getKey())
                            ->where('recordatorio_inicio_enviado_at', $marca)
                            ->update(['recordatorio_inicio_enviado_at' => null]);

                        Log::warning('La reserva se guardó, pero no se pudo enviar su recordatorio de inicio.', [
                            'reserva_id' => $reserva->getKey(),
                            'user_id' => $user->getKey(),
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            }

            $respuesta = redirect()->route('reservas.index')
                ->with('success', '¡Reserva registrada con éxito!');

            if ($notificacionFallida) {
                $respuesta->with('warning', 'La reserva se guardó, pero no se pudieron enviar todas las notificaciones.');
            }

            return $respuesta;

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function show(Reserva $reserva)
    {
        $user = Auth::user();
        // Verificar si puede verla (Admin/Celador o dueño de la reserva)
        if (!$user->hasRole(['Administracion', 'Celador']) && $reserva->user_id !== $user->id) {
            abort(403);
        }

        $reserva->load(['user', 'aula', 'computador.aula', 'bloque']);
        return view('reservas.show', compact('reserva'));
    }

    public function edit(Reserva $reserva)
    {
        $user = Auth::user();
        // Solo administración o el dueño de la reserva pueden editar/revisar su estado administrativo
        if (!$user->hasRole('Administracion') && $reserva->user_id !== $user->id) {
            abort(403);
        }

        return view('reservas.edit', compact('reserva'));
    }

    public function update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,entregada,devuelta,cancelada,no_show',
        ]);

        $reserva->update([
            'estado' => $request->estado,
        ]);

        return redirect()->route('reservas.index')
            ->with('success', '¡Reserva actualizada correctamente!');
    }

    public function destroy(Reserva $reserva)
    {
        try {
            $this->reservaService->cancelar(Auth::user(), $reserva);
            return redirect()->route('reservas.index')
                ->with('success', 'La reserva ha sido cancelada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('reservas.index')
                ->with('error', $e->getMessage());
        }
    }

    public function confirmarIngreso(Reserva $reserva)
    {
        $this->reservaService->aprobar(auth()->user(), $reserva);

        return back()->with('success', 'Reserva confirmada correctamente.');
    }
}