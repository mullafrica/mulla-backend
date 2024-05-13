<?php

namespace App\Services\Interfaces;

interface IWalletService
{
    public function checkBalance(int $amount);
    public function incrementBalance(float $amount);
    public function decrementBalance(float $amount);
}
