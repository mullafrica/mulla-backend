<?php

namespace App\Services;

use App\Enums\BaseUrls;
use App\Jobs\DiscordBots;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Services\Interfaces\IWalletService;
use App\Traits\Reusables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService implements IWalletService
{
    use Reusables;

    public function checkBalance(int $amount)
    {
        if (User::where('id', Auth::id())->first()->wallet >= $amount) {
            return true;
        } else {
            return false;
        }
    }

    public function decrementBalance(float $amount) {
        MullaUserWallets::where('user_id', Auth::id())->decrement('balance', $amount);
    }

    public function incrementBalance(float $amount)
    {
        MullaUserWallets::where('user_id', Auth::id())->increment('balance', $amount);
    }
}
