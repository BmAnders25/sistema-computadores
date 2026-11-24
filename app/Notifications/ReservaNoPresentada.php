<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Notification;

class ReservaNoPresentada extends Notification
{
    public function __construct(private Reserva $reserva) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->reserva->loadMissing(['user', 'aula', 'computador']);
        $recurso = $this->reserva->aula_id
            ? 'aula completa ' . ($this->reserva->aula?->nombre ?? '')
            : trim(($this->reserva->computador?->marca ?? 'computador') . ' ' . ($this->reserva->computador?->modelo ?? ''));

        return [
            'titulo' => 'Reserva marcada como no presentada',
            'mensaje' => ($this->reserva->user?->name ?? 'Un usuario') . ' no se presentó a la reserva de ' . $recurso . '.',
            'url' => route('reservas.show', $this->reserva),
            'tipo' => 'no_show',
            'reserva_id' => $this->reserva->getKey(),
        ];
    }
}
