<?php

namespace App\Enums;

class Cashbacks {
    const ELECTRICITY = 0.5 / Cashbacks::DIVISOR;
    const AIRTIME = 2 / Cashbacks::DIVISOR;
    const TV = 1 / Cashbacks::DIVISOR;
    const DEFAULT = 0.5 / Cashbacks::DIVISOR;
    
    const DIVISOR = 100;
}