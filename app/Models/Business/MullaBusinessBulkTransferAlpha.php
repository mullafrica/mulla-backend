<?php

namespace App\Models\Business;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaBusinessBulkTransferAlpha extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = [
        'updated_at',
    ];

    protected $appends = ['count', 'status'];
    
    // boot() method to generate unique id for each record
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) substr(md5(microtime()), 0, 12);
            }
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M d, Y');
    }

    public function transactions() {
        return $this->hasMany(MullaBusinessBulkTransferTransactionsAlpha::class, 'transfer_id', 'id');
    }

    public function getCountAttribute()
    {
        return $this->transactions()->count();
    }

    public function getStatusAttribute()
    {
        return $this->transactions()->where('status', false)->doesntExist();
    }
}
