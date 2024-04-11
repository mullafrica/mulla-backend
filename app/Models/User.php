<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $guarded = [];

    public $appends = ['wallet', 'cashback_wallet'];

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getWalletAttribute()
    {
        return MullaUserWallets::where('user_id', $this->id)->first() ? MullaUserWallets::where('user_id', $this->id)->first()->balance : 0;
    }

    public function getCashbackWalletAttribute()
    {
        return MullaUserCashbackWallets::where('user_id', $this->id)->first() ? MullaUserCashbackWallets::where('user_id', $this->id)->first()->balance : 0;
    }

    // public function wallet() {
    //     return $this->hasOne(UserWallets::class, 'user_id', 'id');
    // }

    // public function cashback_wallet()
    // {
    //     return $this->hasOne(UserCashbackWallets::class, 'user_id', 'id');
    // }
}
