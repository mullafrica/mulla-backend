<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBulkTransferListItemsModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', 'list_id', 'amount', 'email', 'account_name', 'account_number', 'bank_code', 'bank_name', 'recipient_code'
    ];

    public function setAmountAttribute($value) {
        $this->attributes['amount'] = $value * 100;
    }

    public function getAmountAttribute($value) {
        return $value / 100;
    }

    public function list() {
        return $this->belongsTo(MullaBusinessBulkTransferListModel::class, 'list_id', 'id');
    }
}
