<?php

namespace App\Models\Business;

use App\Traits\UniqueId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MullaBusinessBulkTransferListModel extends Model
{
    use HasFactory, UniqueId;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'business_id', 'title', 'type'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public $appends = ['count'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) substr(md5(microtime()), 0, 12);
            }
        });
    }

    public function items() {
        return $this->hasMany(MullaBusinessBulkTransferListItemsModel::class, 'list_id', 'id');
    }

    public function getCountAttribute() {
        return $this->items()->count();
    }
}
