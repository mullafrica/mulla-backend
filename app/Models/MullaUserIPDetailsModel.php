<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MullaUserIPDetailsModel extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['name', 'email', 'date'];
    protected $hidden = ['user_id', 'user', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute()
    {
        return $this->user->firstname . ' ' . $this->user->lastname;
    }

    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->updated_at)->isoFormat('lll');
    }
}
