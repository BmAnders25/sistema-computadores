<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CodigoVerificacionMail extends Mailable
{
    public function __construct(public string $nombre, public string $codigo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código de verificación - S.R.C.');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.codigo-verificacion');
    }
}