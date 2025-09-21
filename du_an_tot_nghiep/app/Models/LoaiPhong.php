<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoaiPhong extends Model
{
    use HasFactory;

    protected $table = 'loai_phong';

    protected $fillable = [
        'ma',
        'ten',
        'mo_ta',
        'suc_chua',
        'so_giuong',
        'gia_mac_dinh',
        'so_luong_thuc_te',
    ];

    protected $casts = [
        'gia_mac_dinh' => 'decimal:2',
    ];

    // Relationships
    public function phongs()
    {
        return $this->hasMany(Phong::class, 'loai_phong_id');
    }

    public function tienNghis()
    {
        return $this->belongsToMany(TienNghi::class, 'loai_phong_tien_nghi');
    }
}
