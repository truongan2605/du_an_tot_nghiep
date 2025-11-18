<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'so_dien_thoai',
        'phong_ban',
        'vai_tro',
        'is_active',
        'country',
        'dob',
        'gender',
        'address',
        'avatar',
        'provider',
        'provider_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'dob' => 'date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ======= Vai trò =======
    public function isAdmin(): bool
    {
        return $this->vai_tro === 'admin';
    }

    // ======= Quan hệ =======
    public function datPhongs()
    {
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function favoritePhongs()
    {
        return $this->belongsToMany(Phong::class, 'wishlists', 'user_id', 'phong_id')
                    ->withTimestamps();
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }

    // ======= Quan hệ voucher =======
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'user_voucher', 'user_id', 'voucher_id')
                    ->withPivot('claimed_at')
                    ->withTimestamps();
    }
}
