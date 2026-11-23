<?php

namespace App\Notifications;

class RecordatorioFinReserva extends RecordatorioReserva
{
    protected function tipo(): string
    {
        return 'fin';
    }

    protected function titulo(): string
    {
        return 'Tu reserva está por finalizar';
    }

    protected function accion(): string
    {
        return 'está por finalizar';
    }

    protected function instruccion(): ?string
    {
        return 'Recuerda devolver el equipo al celador al terminar.';
    }
}
