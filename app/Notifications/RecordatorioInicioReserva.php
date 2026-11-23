<?php

namespace App\Notifications;

class RecordatorioInicioReserva extends RecordatorioReserva
{
    protected function tipo(): string
    {
        return 'inicio';
    }

    protected function titulo(): string
    {
        return 'Tu reserva está por comenzar';
    }

    protected function accion(): string
    {
        return 'comienza en unos minutos';
    }
}
