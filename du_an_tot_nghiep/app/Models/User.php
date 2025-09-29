<?php

namespace App\Models;

use App\Models\DanhGia;
use App\Models\DatPhong;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail; // Cần dùng cho Accessor/Mutator nếu muốn tùy chỉnh
use Illuminate\Foundation\Auth\User as Authenticatable; // Thêm dòng này để import Model DatPhong
use Illuminate\Database\Eloquent\Factories\HasFactory; // Thêm dòng này để import Model DanhGia

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
        // Bây giờ DatPhong::class đã được định nghĩa
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function danhGias()
    {
        // Bây giờ DanhGia::class đã được định nghĩa
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }
    
    // !!! Đảm bảo phương thức setPasswordAttribute đã được xóa/comment để tránh double-hashing !!!
    /*
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
    */
}
