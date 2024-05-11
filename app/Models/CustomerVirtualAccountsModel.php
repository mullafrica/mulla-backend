<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerVirtualAccountsModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['id', 'created_at', 'updated_at', 'customer_id', 'user_id', 'bank_id', 'bank_slug'];
}
