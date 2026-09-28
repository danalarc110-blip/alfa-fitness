<?php

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComprobanteVentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Venta $venta)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante de Compra #'.$this->venta->id.' · Alpha Fitness',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comprobante-venta',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
