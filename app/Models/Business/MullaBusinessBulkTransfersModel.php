<?php

namespace App\Models\Business;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBulkTransfersModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'reference',
        'currency',
        'reason',
        'status'
    ];

    protected $appends = [
        'total_amount',
        'count'
    ];

    public function transactions() {
        return $this->hasMany(MullaBusinessBulkTransferTransactions::class, 'bulk_transfer_id', 'id');
    }

    public function getCountAttribute()
    {
        return $this->transactions()->count();
    }

    public function getTotalAmountAttribute()
    {
        return number_format($this->transactions()->sum('amount') / 100, 2);
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
