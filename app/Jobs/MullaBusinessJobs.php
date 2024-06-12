<?php

namespace App\Jobs;

use App\Enums\BaseUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class MullaBusinessJobs implements ShouldQueue
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
        if ($this->data['type'] === 'delete_trf_recipients') {
            foreach ($this->data['list'] as $item) {
                Http::withToken(env('MULLA_PAYSTACK_LIVE'))->delete(BaseUrls::PAYSTACK . 'transferrecipient/' . $item->recipient_code);
            }
        }
    }
}
