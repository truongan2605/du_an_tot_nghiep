<?php

namespace App\Models;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->vai_tro === 'admin';
    }

    public function datPhongs()
    {
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);  // Hash password
    }
}
