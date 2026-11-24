<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ReservaConfirmada extends Notification
{
    public function __construct(private Reserva $reserva) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Reserva registrada',
            'mensaje' => $this->mensaje(),
            'url' => route('reservas.index'),
            'tipo' => 'confirmacion',
            'reserva_id' => $this->reserva->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $datos = $this->toArray($notifiable);

        return (new MailMessage())
            ->subject($datos['titulo'])
            ->greeting('Hola ' . $notifiable->name)
            ->line($datos['mensaje'])
            ->action('Ver mis reservas', $datos['url'])
            ->salutation('S.R.C. · Universidad de Pamplona');
    }

    private function mensaje(): string
    {
        $this->reserva->loadMissing(['aula', 'bloque', 'computador']);
        $fecha = Carbon::parse(
            $this->reserva->fecha->toDateString(),
            config('app.timezone')
        )->locale('es')->translatedFormat('d \d\e F \d\e Y');
        $inicio = Carbon::parse(
            $this->reserva->fecha->toDateString() . ' ' . $this->reserva->bloque->hora_inicio,
            config('app.timezone')
        )->format('H:i');
        $fin = Carbon::parse(
            $this->reserva->fecha->toDateString() . ' ' . $this->reserva->bloque->hora_fin,
            config('app.timezone')
        )->format('H:i');
        $recurso = $this->reserva->aula_id
            ? 'aula completa ' . $this->reserva->aula->nombre
            : trim(($this->reserva->computador?->marca ?? 'computador') . ' ' . ($this->reserva->computador?->modelo ?? ''));

        return "Tu reserva de {$recurso} quedó registrada para el {$fecha}, en el bloque {$this->reserva->bloque->nombre} ({$inicio} a {$fin}).";
    }
}
