<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

abstract class RecordatorioReserva extends Notification
{
    public function __construct(protected Reserva $reserva) {}

    abstract protected function tipo(): string;

    abstract protected function titulo(): string;

    abstract protected function accion(): string;

    protected function instruccion(): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo(),
            'mensaje' => $this->mensaje(),
            'url' => route('reservas.index'),
            'tipo' => $this->tipo(),
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

    protected function mensaje(): string
    {
        $bloque = $this->reserva->bloque;
        $fecha = Carbon::parse(
            $this->reserva->fecha->toDateString(),
            config('app.timezone')
        )->locale('es')->translatedFormat('d \d\e F \d\e Y');
        $inicio = $bloque
            ? Carbon::parse($this->reserva->fecha->toDateString() . ' ' . $bloque->hora_inicio, config('app.timezone'))->format('H:i')
            : null;
        $fin = $bloque
            ? Carbon::parse($this->reserva->fecha->toDateString() . ' ' . $bloque->hora_fin, config('app.timezone'))->format('H:i')
            : null;
        $horario = $inicio && $fin ? "{$inicio} a {$fin}" : 'el horario reservado';
        $nombreBloque = $bloque?->nombre ?? 'bloque horario';
        $recurso = $this->reserva->aula_id
            ? 'aula completa'
            : trim(($this->reserva->computador?->marca ?? 'computador') . ' ' . ($this->reserva->computador?->modelo ?? ''));

        $mensaje = "Tu reserva de {$recurso} {$this->accion()} en {$nombreBloque} ({$horario}), el {$fecha}.";

        if ($instruccion = $this->instruccion()) {
            $mensaje .= ' ' . $instruccion;
        }

        return $mensaje;
    }
}
