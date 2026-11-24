<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ReservaConfirmada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class HerramientasPruebaController extends Controller
{
    public function index(): View
    {
        $usuarios = User::query()
            ->where('email', 'like', 'prueba.%')
            ->with('roles')
            ->orderBy('email')
            ->get();
        $reservasPorUsuario = Reserva::query()
            ->whereIn('user_id', $usuarios->modelKeys())
            ->with('bloque')
            ->latest('id')
            ->get()
            ->groupBy('user_id');

        return view('herramientas-prueba.index', compact('usuarios', 'reservasPorUsuario'));
    }

    public function recordatorios(): RedirectResponse
    {
        $codigo = Artisan::call('reservas:recordatorios');

        return redirect()
            ->route('herramientas.prueba')
            ->with($codigo === 0 ? 'resultado' : 'error', trim(Artisan::output()));
    }

    public function reiniciarEscenarios(): RedirectResponse
    {
        $salidas = [];
        foreach ([
            \Database\Seeders\PruebaRecordatorioInicioSeeder::class,
            \Database\Seeders\PruebaRecordatorioFinSeeder::class,
            \Database\Seeders\PruebaRecordatorioAulaSeeder::class,
        ] as $seeder) {
            $codigo = Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
            $salidas[] = trim(Artisan::output());

            if ($codigo !== 0) {
                return redirect()
                    ->route('herramientas.prueba')
                    ->with('error', implode(PHP_EOL, $salidas));
            }
        }

        return redirect()
            ->route('herramientas.prueba')
            ->with('resultado', implode(PHP_EOL, $salidas));
    }

    public function impersonar(User $user, Request $request): RedirectResponse
    {
        abort_unless(str_starts_with($user->email, 'prueba.'), 404);

        $request->session()->put('impersonador_id', $request->user()->getKey());
        Auth::login($user);
        $request->session()->put('two_factor_passed', true);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function volver(Request $request): RedirectResponse
    {
        $impersonadorId = $request->session()->get('impersonador_id');
        abort_unless(is_numeric($impersonadorId), 403);

        $administrador = User::query()->findOrFail($impersonadorId);
        abort_unless($administrador->hasRole('Administracion'), 403);

        Auth::login($administrador);
        $request->session()->forget('impersonador_id');
        $request->session()->put('two_factor_passed', true);
        $request->session()->regenerate();

        return redirect()->route('herramientas.prueba');
    }

    public function enviarConfirmacion(User $user): RedirectResponse
    {
        abort_unless(str_starts_with($user->email, 'prueba.'), 404);

        $reserva = $user->reservas()->latest('id')->first();
        if (! $reserva) {
            return redirect()
                ->route('herramientas.prueba')
                ->with('error', "El usuario {$user->email} no tiene reservas para confirmar.");
        }

        $confirmacion = new ReservaConfirmada($reserva);
        Notification::sendNow($user, $confirmacion, ['database']);

        try {
            Notification::sendNow($user, $confirmacion, ['mail']);
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar el correo de confirmación de prueba.', [
                'reserva_id' => $reserva->getKey(),
                'user_id' => $user->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('herramientas.prueba')
                ->with('warning', 'La confirmación quedó en la campana, pero no se pudo enviar el correo.');
        }

        return redirect()
            ->route('herramientas.prueba')
            ->with('success', "Se envió la confirmación de la reserva #{$reserva->getKey()} a {$user->email}.");
    }
}
