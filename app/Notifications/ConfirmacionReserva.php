<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ConfirmacionReserva extends Notification
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
            'mensaje' => 'Tu solicitud de reserva fue registrada y está pendiente de aprobación.',
            'url' => route('reservas.index'),
            'tipo' => 'confirmacion',
            'reserva_id' => $this->reserva->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->reserva->loadMissing(['aula', 'bloque', 'computador']);
        $fecha = Carbon::parse(
            $this->reserva->fecha->toDateString(),
            config('app.timezone')
        )->locale('es')->translatedFormat('d \d\e F \d\e Y');
        $horaInicio = Carbon::parse(
            $this->reserva->fecha->toDateString() . ' ' . $this->reserva->bloque->hora_inicio,
            config('app.timezone')
        )->format('H:i');
        $horaFin = Carbon::parse(
            $this->reserva->fecha->toDateString() . ' ' . $this->reserva->bloque->hora_fin,
            config('app.timezone')
        )->format('H:i');

        if ($this->reserva->aula_id !== null) {
            $recurso = 'aula completa ' . $this->reserva->aula->nombre;
        } else {
            $computador = $this->reserva->computador;
            $recurso = trim(
                ($computador?->marca ?? 'Computador') . ' ' .
                ($computador?->modelo ?? '')
            );
            if ($computador?->serial) {
                $recurso .= ' (serial ' . $computador->serial . ')';
            }
        }

        return (new MailMessage())
            ->subject('Solicitud de reserva recibida')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Registramos tu solicitud de reserva y está pendiente de aprobación.')
            ->line('Recurso: ' . $recurso . '.')
            ->line('Fecha: ' . $fecha . '.')
            ->line('Horario: ' . $this->reserva->bloque->nombre . ', de ' . $horaInicio . ' a ' . $horaFin . '.')
            ->action('Ver mis reservas', route('reservas.index'))
            ->salutation('S.R.C. · Universidad de Pamplona');
    }
}
