<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBankAccountsModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function business()
    {
        return $this->belongsTo(
            MullaBusinessAccountsModel::class,
            "business_id",
            "id"
        );
    }
}
