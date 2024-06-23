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

    protected $appends = ['account', 'formatted_amount'];

    public function getAccountAttribute()
    {
        return MullaBusinessBulkTransferListItemsModel::where('recipient_code', $this->attributes['recipient_code'])->first([
            'account_name',
            'bank_name',
            'account_number'
        ]);
    }

    public function getFormattedAmountAttribute() {
        return number_format($this->attributes['amount'] / BaseUrls::MULTIPLIER);
    }

    public function getCreatedAtAttribute($value)
    {
       return Carbon::parse($value)->format('M d, Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M d, Y');
    }
}
