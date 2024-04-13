<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaUserTransactions extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $appends = ['date', 'amt'];

    protected $hidden = ['created_at', 'updated_at', 'amount'];

    public function getDateAttribute() {
        // Make the date like Monday, 12 Jun 2023
        return $this->created_at->format('D dS M \a\t h:i A');
    }

    public function getAmtAttribute() {
        return 'NGN ' . number_format($this->amount, 2);
    }
}
