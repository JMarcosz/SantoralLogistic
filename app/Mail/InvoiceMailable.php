<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public string $recipientEmail,
        public ?string $customMessage = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Factura Fiscal {$this->invoice->number} - {$this->invoice->customer->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $this->invoice->load(['customer', 'lines', 'shippingOrder', 'preInvoice']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
        ]);

        return [
            Attachment::fromData(fn() => $pdf->output(), "factura_{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
