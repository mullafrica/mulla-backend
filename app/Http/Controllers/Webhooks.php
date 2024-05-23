<?php

namespace App\Http\Controllers;

use App\Jobs\Jobs;
use App\Jobs\WebhookJobs;
use Illuminate\Http\Request;

class Webhooks extends Controller
{
    public function blocWebhooks(Request $request)
    {
        $data = $request->all();

        if ($data['event'] == 'transaction.updated') {

            Jobs::dispatch([
                'type' => 4,
                'res' => $data['data']
            ]);

            return response('OK', 200);
        }

        return response('OK', 200);
    }

    public function all(Request $request)
    {
        if ($request->ip() === '52.31.139.75' || $request->ip() === '52.49.173.169' || $request->ip() === '52.214.14.220') {
            $data = $request->all();
            WebhookJobs::dispatch($data);
            return response('OK', 200);
        }

        return;
    }
}
