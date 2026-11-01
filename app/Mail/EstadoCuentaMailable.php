<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstadoCuentaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $asociado;
    public $rutaArchivo;

    /**
     * Create a new message instance.
     *
     * @param mixed $asociado Información del asociado.
     * @param string $rutaArchivo Ruta relativa del archivo PDF.
     */
    public function __construct($asociado, $rutaArchivo)
    {
        //dd($rutaArchivo);
        $this->asociado = $asociado;
        $this->rutaArchivo = $rutaArchivo;
    }

    /**
     * Build the email.
     */
    public function build()
    {
        // Obtener la ruta completa del archivo
        $rutaCompleta = storage_path('app/' . $this->rutaArchivo);

        // Validar que el archivo exista antes de adjuntarlo
        if (!file_exists($rutaCompleta)) {
            throw new \Exception("El archivo especificado no existe: {$rutaCompleta}");
        }

        // Construir el correo con el destinatario y el adjunto
        return $this->to($this->asociado->email) // Dirección de correo del asociado
            ->subject('Estado de Cuenta de ' . $this->asociado->nombre)
            ->view('emails.estado_cuenta', ['asociado' => $this->asociado])
            ->attach($rutaCompleta, [
                'as' => 'estado_cuenta_' . $this->asociado->id . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Estado de Cuenta Mailable',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.estado_cuenta',
            with: [
                'asociado' => $this->asociado,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Si quieres usar la nueva forma de adjuntar, puedes usar esta función
        return [];
    }
}
