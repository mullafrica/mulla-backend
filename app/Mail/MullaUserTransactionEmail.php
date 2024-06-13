<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MullaUserTransactionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['firstname'] . ', your transaction was successful.',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mulla-user-transaction-email',
            with: [
                'firstname' => $this->data['firstname'],
                'utility' => $this->data['utility'],
                'amount' => $this->data['amount'],
                'date' => $this->data['date'],
                'cashback' => $this->data['cashback'],
                'code' => $this->data['code'],
                'serial' => $this->data['serial'],
                'token' => $this->data['token'],
                'units' => $this->data['units'],
                'device_id' => $this->data['device_id'],
                'transaction_reference' => $this->data['transaction_reference']
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
        return [];
    }
}
