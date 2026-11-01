<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ComputadorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\Auth\TwoFactorController;

Auth::routes(['register' => false]);

// Segundo factor: requiere sesión, pero NO el middleware two-factor (evita bucle)
Route::middleware('auth')->group(function () {
    Route::middleware(['auth'])->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:3,1')->name('two-factor.resend');
});

Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])
    ->middleware('throttle:3,1')->name('two-factor.resend');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
    Route::get('/logout', [LogoutController::class, 'logout'])->name('auth.logout');
});

Route::middleware(['auth', 'two-factor'])->group(function () {
    
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::redirect('/home', '/');

    Route::get('/profile', fn () => view('profile'))->name('profile');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:ver-estadistica')->name('dashboard');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])
        ->name('configuracion.index');

    Route::resource('computadores', ComputadorController::class)
        ->parameters(['computadores' => 'computador']);

    Route::resource('users', UserController::class);
    Route::resource('roles', RolController::class);
    Route::resource('permissions', PermissionController::class);


    
});

