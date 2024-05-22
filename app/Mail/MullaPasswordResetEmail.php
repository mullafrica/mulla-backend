<?php

namespace App\Mail;

use App\Traits\Reusables;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MullaPasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels, Reusables;

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
            subject: 'Password Reset Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $info = $this->getUserDetails($this->data['ip']);

        return new Content(
            markdown: 'mail.mulla-password-reset-email',
            with: [
                'firstname' => $this->data['firstname'],
                'datetime' => Carbon::parse(now())->isoFormat('lll'),
                'browser' => $info['browser'],
                'os' => $info['platform'],
                'location' => $info['location']['city'] . ', ' . $info['location']['country'],
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
