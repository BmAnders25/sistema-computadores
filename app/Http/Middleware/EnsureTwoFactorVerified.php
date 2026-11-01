<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    if ($user && !$user->esActivo()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Tu cuenta institucional no está activa. Comunícate con Administración.',
        ]);
    }

    if ($user && $user->requiereDosPasos() && !$request->session()->get('two_factor_passed')) {
        return redirect()->route('two-factor.show');
    }

    return $next($request);
}
}