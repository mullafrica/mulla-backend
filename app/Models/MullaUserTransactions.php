<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MullaUserTransactions extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $appends = ['date', 'amt', 'address', 'name'];

    protected $hidden = ['created_at', 'updated_at', 'amount'];

    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->updateUserStatsCache($transaction->user_id);
        });

        static::updated(function ($transaction) {
            $transaction->updateUserStatsCache($transaction->user_id);
        });
    }

    private function updateUserStatsCache($userId)
    {
        Cache::put('user_stats_cache' . $userId, [
            'total_amount_spent' => self::where('user_id', $userId)->where('status', 1)->whereNot('type', 'Bank Transfer')->sum('amount'),
            'total_transaction_count' => self::where('user_id', $userId)->where('status', 1)->count(),
            'spend_by_type' => [
                'electricity' => self::where('user_id', $userId)->where('status', 1)->where('type', 'Electricity Bill')->sum('amount'),
                'airtime' => self::where('user_id', $userId)->where('status', 1)->where('type', 'Airtime Recharge')->sum('amount'),
                'tv' => self::where('user_id', $userId)->where('status', 1)->where('type', 'TV Subscription')->sum('amount'),
                'internet_data' => self::where('user_id', $userId)->where('status', 1)->where('type', 'Data Services')->sum('amount'),
            ]
        ], 60 * 24 * 24);
    }


    public function getDateAttribute()
    {
        // Make the date like Monday 12 Jun 2023 at 12:00 AM
        return $this->created_at->format('D dS M \a\t h:i A');
    }

    public function getAmtAttribute()
    {
        return number_format($this->amount, 2) . ' NGN';
    }

    public function getNameAttribute()
    {
        if ($this->type !== 'Electricity Bill') {
            return null;
        }
        return User::where('id', $this->user_id)->first()->firstname . ' ' . User::where('id', $this->user_id)->first()->lastname;
    }

    public function getAddressAttribute()
    {
        if (!$this->unique_element) {
            return null;
        }
        return MullaUserMeterNumbers::where('meter_number', $this->unique_element)->first()->address ?? null;
    }
}
