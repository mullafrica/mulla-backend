<?php

namespace App\Models\Business;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBulkTransferTransactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulk_transfer_id',
        'reference',
        'pt_recipient_id',
        'currency',
        'amount',
        'recipient_account_no',
        'recipient_account_name',
        'recipient_bank',
        'status',
    ];

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = $value * 100;
    }

    public function getAmountAttribute($value)
    {
        return $value / 100;
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