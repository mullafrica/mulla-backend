<?php

namespace App\Enums;

class BaseUrls
{
    const PAYSTACK = "https://api.paystack.co/";
    
    const BANK = "test-bank";

    public static function getBank()
    {
        return config('app.env') === 'local' ? 'test-bank' : 'wema-bank';
    }
}
