<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class NguoiDung extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'nguoi_dung';

    protected $fillable = [
        'ten',
        'email',
        'so_dien_thoai',
        'mat_khau_hash',
        'vai_tro',
        'phong_ban',
        'is_active',
    ];

    protected $hidden = [
        'mat_khau_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mat_khau_hash' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function datPhongs()
    {
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }

}
