<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function show()
    {
        return view('auth.two-factor');
    }

    public function verify(Request $request, TwoFactorService $service)
    {
        $request->validate(['codigo' => 'required|digits:6']);

        if (!$service->verificar($request->user(), $request->codigo)) {
            return back()->withErrors(['codigo' => 'Código inválido o vencido.']);
        }

        $request->session()->put('two_factor_passed', true);
        return redirect()->intended('/');
    }

    public function resend(Request $request, TwoFactorService $service)
    {
        $codigo = $service->generar($request->user());
        if (app()->environment('local')) {
            session()->flash('two_factor_demo', $codigo);
        }
        return back()->with('status', 'Te enviamos un nuevo código.');
    }
}