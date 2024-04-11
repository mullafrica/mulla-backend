<?php

namespace App\Traits;

trait Defaults
{
    protected function key()
    {
        return env('MONO_LIVE');
    }

    protected function decrypt($encryptedData, $secretKey)
    {
        $encryptedBin = hex2bin($encryptedData);

        $iv = substr($encryptedBin, 0, 16);

        $encryptedText = substr($encryptedBin, 16);

        $key = substr(base64_encode(hash('sha256', $secretKey, true)), 0, 32);

        $algorithm = "aes-256-cbc";

        return openssl_decrypt($encryptedText, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
    }

    protected function fee() {
        return 100;
    }
}
