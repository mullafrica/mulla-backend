<?php

namespace App\Services;

use App\Enums\BaseUrls;
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

    public function checkBalanceOnly(int $amount) {
        if (User::where('id', Auth::id())->first()->wallet * BaseUrls::MULTIPLIER >= $amount) {
            return true;
        } else {
            return false;
        }
    }

    public function checkDecrementBalance(int $amount)
    {
        try {
            DB::beginTransaction();

            $wallet = MullaUserWallets::where('user_id', Auth::id())
                ->lockForUpdate()
                ->first();

            $amountInKobo = $amount * BaseUrls::MULTIPLIER;

            if (!$this->hasEnoughBalance($wallet, $amountInKobo)) {
                DB::rollBack();
                return false;
            }

            $wallet->decrement('balance', $amount);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet transaction failed', [
                'user_id' => Auth::id(),
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
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

    private function hasEnoughBalance(?MullaUserWallets $wallet, int $amount): bool
    {
        return $wallet && ($wallet->balance * BaseUrls::MULTIPLIER >= $amount);
    }
}
