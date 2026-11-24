<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\RecordatorioInicioReserva;
use App\Notifications\ReservaConfirmada;
use App\Notifications\ReservaNoPresentada;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
            $like = '%'.$search.'%';
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
            'tipo' => 'required|in:equipo,aula',
            'fecha' => 'required|date|after_or_equal:today',
            'bloque_horario_id' => 'required|exists:bloques_horarios,id',
            'computador_id' => 'required_if:tipo,equipo|nullable|exists:computadores,id',
            'aula_id' => 'required_if:tipo,aula|nullable|exists:aulas,id',
        ]);

        $user = Auth::user();

        try {
            if ($request->tipo === 'aula') {
                // Validar que tenga el permiso explícito para reservar aulas completas
                if (! $user->can('reservar-aulas')) {
                    throw ValidationException::withMessages(['reserva' => 'No tienes permisos para reservar aulas completas.']);
                }
                $reserva = $this->reservaService->reservarAula($user, $request->aula_id, $request->fecha, $request->bloque_horario_id);
            } else {
                $reserva = $this->reservaService->reservarEquipo($user, $request->computador_id, $request->fecha, $request->bloque_horario_id);
            }

            $correoFallido = false;
            $confirmacion = new ReservaConfirmada($reserva);
            Notification::sendNow($user, $confirmacion, ['database']);

            try {
                Notification::sendNow($user, $confirmacion, ['mail']);
            } catch (Throwable $exception) {
                $correoFallido = true;
                Log::warning('La reserva se guardó, pero no se pudo enviar su correo de confirmación.', [
                    'reserva_id' => $reserva->getKey(),
                    'user_id' => $user->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }

            $reserva->loadMissing(['bloque', 'computador', 'aula']);
            $ahora = Carbon::now(config('app.timezone'));
            $inicio = Carbon::parse(
                $reserva->fecha->toDateString().' '.$reserva->bloque->hora_inicio,
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
                        // La confirmación ya envía un correo; el recordatorio inmediato queda en la campana para evitar otro envío SMTP seguido.
                        Notification::sendNow(
                            $user,
                            new RecordatorioInicioReserva($reserva),
                            ['database']
                        );
                    } catch (Throwable $exception) {
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

            if ($correoFallido) {
                $respuesta->with('warning', 'La reserva se guardó y la campana se actualizó, pero no se pudo enviar el correo.');
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
        if (! $user->hasRole(['Administracion', 'Celador']) && $reserva->user_id !== $user->id) {
            abort(403);
        }

        $reserva->load(['user', 'aula', 'computador.aula', 'bloque']);

        return view('reservas.show', compact('reserva'));
    }

    public function edit(Reserva $reserva)
    {
        $user = Auth::user();
        // Solo administración o el dueño de la reserva pueden editar/revisar su estado administrativo
        if (! $user->hasRole('Administracion') && $reserva->user_id !== $user->id) {
            abort(403);
        }

        return view('reservas.edit', compact('reserva'));
    }

    public function marcarNoPresentada(Request $request, Reserva $reserva)
    {
        $usuario = $request->user();

        abort_unless($usuario->hasAnyRole(['Celador', 'Administracion']), 403);

        if ($reserva->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'reserva' => 'Solo se puede marcar como no presentada una reserva pendiente.',
            ]);
        }

        if ($usuario->hasRole('Celador')) {
            $reserva->loadMissing('bloque');
            $inicio = $reserva->bloque?->inicioEn($reserva->fecha->toDateString());

            if (! $inicio || ! now(config('app.timezone'))->greaterThan($inicio)) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo puedes marcar una reserva como no presentada después del inicio de su bloque horario.',
                ]);
            }
        }

        $request->merge(['estado' => 'no_show']);

        return $this->update($request, $reserva);
    }

    public function update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,entregada,devuelta,cancelada,no_show',
        ]);

        $estadoAnterior = $reserva->estado;
        $estadoNuevo = $request->input('estado');
        $estadosCerrados = ['devuelta', 'cancelada', 'no_show'];

        if ($estadoAnterior !== $estadoNuevo
            && in_array($estadoAnterior, $estadosCerrados, true)
            && in_array($estadoNuevo, Reserva::ACTIVAS, true)) {
            throw ValidationException::withMessages([
                'estado' => 'No es posible reactivar una reserva cerrada. Debes crear una nueva reserva.',
            ]);
        }

        if ($estadoAnterior !== $estadoNuevo && $estadoNuevo === 'entregada' && $estadoAnterior !== 'pendiente') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede marcar como entregada una reserva que esté pendiente.',
            ]);
        }

        if ($estadoAnterior !== $estadoNuevo && $estadoNuevo === 'devuelta' && $estadoAnterior !== 'entregada') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede marcar como devuelta una reserva que esté en uso (entregada).',
            ]);
        }

        if ($estadoNuevo === 'entregada' && $estadoAnterior === 'pendiente') {
            $reserva->update([
                'estado' => $estadoNuevo,
                'entregado_at' => now(),
            ]);
        } elseif (in_array($estadoNuevo, $estadosCerrados, true)) {
            if ($estadoNuevo === 'devuelta' && $estadoAnterior === 'entregada') {
                $reserva->entregado_at = $reserva->entregado_at ?? now();
                $reserva->devuelto_at = now();
            }

            $reserva->liberar($estadoNuevo);
        } else {
            $reserva->update([
                'estado' => $estadoNuevo,
            ]);
        }

        if ($reserva->wasChanged('estado')
            && $reserva->estado === 'no_show'
            && ! DatabaseNotification::query()
                ->where('type', ReservaNoPresentada::class)
                ->where('data->reserva_id', $reserva->getKey())
                ->exists()) {
            try {
                Notification::sendNow(
                    User::role('Celador')->get(),
                    new ReservaNoPresentada($reserva),
                    ['database']
                );
            } catch (Throwable $exception) {
                Log::warning('No se pudo notificar a los celadores sobre una reserva no presentada.', [
                    'reserva_id' => $reserva->getKey(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

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
