<?php

namespace App\Models\Business;

use App\Enums\BaseUrls;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBulkTransferTransactionsAlpha extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    protected $appends = ['account'];

    public function getAccountAttribute()
    {
        return MullaBusinessBulkTransferListItemsModel::where('recipient_code', $this->attributes['recipient_code'])->first([
            'account_name',
            'bank_name'
        ]);
    }


    public function getAmountAttribute($value) {
        return number_format($value / BaseUrls::MULTIPLIER);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('D dS M \a\t h:i A');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('D dS M \a\t h:i A');
    }
}
