<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ComputadorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\BloqueHorarioController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\ReporteOcupacionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\HerramientasPruebaController;
use App\Http\Middleware\EnsureLocalEnvironment;
Auth::routes(['register' => false]);

// Segundo factor: requiere sesión, pero NO el middleware two-factor (evita bucle)
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:3,1')->name('two-factor.resend');
    Route::get('/logout', [LogoutController::class, 'logout'])->name('auth.logout');
});

Route::middleware(['auth', 'two-factor'])->group(function () {
    
    Route::get('/', [DisponibilidadController::class, 'index'])->name('home')
        ->middleware('permission:ver-disponibilidad');
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])
        ->middleware('permission:ver-disponibilidad')
        ->name('disponibilidad.index');
    Route::get('/entregas', [EntregaController::class, 'index'])->name('entregas.index');
    Route::post('/entregas/{reserva}/entregar', [EntregaController::class, 'registrarEntrega'])
        ->name('entregas.entregar');
    Route::post('/entregas/{reserva}/devolver', [EntregaController::class, 'registrarDevolucion'])
        ->name('entregas.devolver');
    Route::redirect('/home', '/');

    Route::get('/profile', fn () => view('profile'))->name('profile');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:ver-estadistica')->name('dashboard');
    Route::get('/dashboard/datos', [DashboardController::class, 'datos'])
        ->middleware('can:ver-estadistica')->name('dashboard.datos');

    Route::get('/reportes/ocupacion', [ReporteOcupacionController::class, 'index'])
        ->middleware('can:ver-reportes')->name('reportes.ocupacion');
    Route::get('/reportes/ocupacion/excel', [ReporteOcupacionController::class, 'excel'])
        ->middleware('can:ver-reportes')->name('reportes.ocupacion.excel');
    Route::get('/reportes/ocupacion/pdf', [ReporteOcupacionController::class, 'pdf'])
        ->middleware('can:ver-reportes')->name('reportes.ocupacion.pdf');

    Route::get('/notificaciones', [NotificacionController::class, 'index'])
        ->name('notificaciones.index');
    Route::get('/notificaciones/resumen', [NotificacionController::class, 'resumen'])
        ->name('notificaciones.resumen');
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'])
        ->name('notificaciones.leer-todas');
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'leer'])
        ->name('notificaciones.leer');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])
        ->name('configuracion.index');

    Route::resource('computadores', ComputadorController::class)
        ->parameters(['computadores' => 'computador']);
    Route::resource('aulas', AulaController::class);
    Route::resource('bloques-horarios', BloqueHorarioController::class)->parameters([
    'bloques-horarios' => 'bloqueHorario'
]);
    Route::resource('reservas', ReservaController::class);
    Route::post('/reservas/{reserva}/no-presentada', [ReservaController::class, 'marcarNoPresentada'])
        ->middleware(['permission:registrar-entregas', 'role:Celador|Administracion'])
        ->name('reservas.no-presentada');
    Route::patch('/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmarIngreso'])
    ->name('reservas.confirmar')
    ->middleware(['auth', 'role:Celador|Administracion']); 
    Route::resource('users', UserController::class);
    Route::resource('roles', RolController::class);
    Route::resource('permissions', PermissionController::class);

    Route::middleware([EnsureLocalEnvironment::class, 'role:Administracion'])->group(function () {
        Route::get('/herramientas-prueba', [HerramientasPruebaController::class, 'index'])
            ->name('herramientas.prueba');
        Route::post('/herramientas-prueba/recordatorios', [HerramientasPruebaController::class, 'recordatorios'])
            ->name('herramientas.prueba.recordatorios');
        Route::post('/herramientas-prueba/reiniciar', [HerramientasPruebaController::class, 'reiniciarEscenarios'])
            ->name('herramientas.prueba.reiniciar');
        Route::post('/herramientas-prueba/usuarios/{user}/impersonar', [HerramientasPruebaController::class, 'impersonar'])
            ->name('herramientas.prueba.impersonar');
        Route::post('/herramientas-prueba/usuarios/{user}/confirmacion', [HerramientasPruebaController::class, 'enviarConfirmacion'])
            ->name('herramientas.prueba.confirmacion');
    });

    Route::post('/herramientas-prueba/volver', [HerramientasPruebaController::class, 'volver'])
        ->middleware(EnsureLocalEnvironment::class)
        ->name('herramientas.prueba.volver');

    
});

