<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        // Validar estado activo de la cuenta institucional
        if (!$user->esActivo()) {
            $this->guard()->logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                $this->username() => 'Tu cuenta institucional no está activa. Comunícate con Administración.',
            ]);
        }

        // Segundo factor
        if ($user->requiereDosPasos()) {
            $codigo = app(TwoFactorService::class)->generar($user);
            $request->session()->put('two_factor_passed', false);

            // Solo para demostración en local: muestra el código en pantalla
            if (app()->environment('local')) {
                session()->flash('two_factor_demo', $codigo);
            }

            return redirect()->route('two-factor.show');
        }

        $request->session()->put('two_factor_passed', true);
        return null; // continúa con el redirect normal
    }
}