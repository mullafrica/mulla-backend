<?php

namespace App\Http\Controllers;

use App\Jobs\Jobs;
use Illuminate\Http\Request;

class Webhooks extends Controller
{
    public function blocWebhooks(Request $request) {
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

    public function cometWebhooks(Request $request) {
        $data = $request->all();

        Jobs::dispatch($request->all());
        return response('OK', 200);
    }
}
