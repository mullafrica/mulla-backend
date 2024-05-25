<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaUserTransactions extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $appends = ['date', 'amt', 'address', 'name'];

    protected $hidden = ['created_at', 'updated_at', 'amount'];

    public function getDateAttribute()
    {
        // Make the date like Monday, 12 Jun 2023
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
