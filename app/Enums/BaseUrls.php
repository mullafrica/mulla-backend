<?php

namespace App\Enums;

class BaseUrls
{
    const PAYSTACK = "https://api.paystack.co/";
    
    const BANK = "test-bank";

    const MULTIPLIER = 100;

    public static function getBank()
    {
        return config('app.env') === 'local' ? 'test-bank' : 'titan-paystack';
    }
}
