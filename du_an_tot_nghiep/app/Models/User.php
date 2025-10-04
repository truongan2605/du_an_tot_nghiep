<?php

namespace App\Models;

use App\Models\DanhGia;
use App\Models\DatPhong;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

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

    public function isAdmin(): bool
    {
        return $this->vai_tro === 'admin';
    }

    public function datPhongs()
    {
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function wishlists()
    {
        return $this->hasMany(\App\Models\Wishlist::class);
    }

    public function favoritePhongs()
    {
        return $this->belongsToMany(\App\Models\Phong::class, 'wishlists', 'user_id', 'phong_id')->withTimestamps();
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }
}
