<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('es');

        return view('notificaciones.index', [
            'notificaciones' => $request->user()->notifications()->latest()->paginate(15),
        ]);
    }

    public function resumen(Request $request): JsonResponse
    {
        Carbon::setLocale('es');
        $usuario = $request->user();
        $notificaciones = $usuario->notifications()->latest()->limit(5)->get();

        return response()->json([
            'label' => $usuario->unreadNotifications()->count(),
            'label_color' => 'danger',
            'icon_color' => 'white',
            'dropdown' => view('notificaciones._dropdown', compact('notificaciones'))->render(),
        ]);
    }

    public function leerTodas(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => Carbon::now(config('app.timezone'))]);

        return redirect()
            ->route('notificaciones.index')
            ->with('success', 'Todas las notificaciones quedaron marcadas como leídas.');
    }

    public function leer(Request $request, string $id)
    {
        /** @var DatabaseNotification $notificacion */
        $notificacion = $request->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        $url = $notificacion->data['url'] ?? route('reservas.index');
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (($host !== null && ($host !== $request->getHost() || ! in_array($scheme, ['http', 'https'], true)))
            || ($host === null && (! is_string($url) || ! str_starts_with($url, '/')))) {
            $url = route('reservas.index');
        }

        return redirect()->to($url);
    }
}
