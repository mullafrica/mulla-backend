<?php

namespace App\Jobs;

use App\Mail\MullaPasswordResetEmail;
use App\Mail\MullaResetTokenEmail;
use App\Mail\MullaWelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class Jobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->data['type'] == 1) {
            $email = new MullaWelcomeEmail();
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] == 2) {
            $email = new MullaResetTokenEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] == 3) {
            $email = new MullaPasswordResetEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] == 4) {
        }
    }
}
