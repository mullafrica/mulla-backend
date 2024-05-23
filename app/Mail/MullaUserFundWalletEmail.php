<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MullaUserFundWalletEmail extends Mailable
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
            subject: 'You Funded your Mulla Wallet',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.mulla-user-fund-wallet-email',
            with: [ 
                'firstname' => $this->data['firstname'],
                'amount' => number_format($this->data['amount'], 2) . ' NGN',
                'fee' => number_format($this->data['fee'], 2) . ' NGN',
                'sender' => $this->data['sender'],
                'bank' => $this->data['bank'],
                'transaction_reference' => $this->data['transaction_reference'],
                'description' => $this->data['description'],
                'date' => $this->data['date'],
                'status' => 'Successful'
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
