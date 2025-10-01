<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Phong extends Model
{
    use HasFactory;

    protected $table = 'phong';

    protected $fillable = [
        'ma_phong',
        'loai_phong_id',
        'tang_id',
        'suc_chua',
        'so_giuong',
        'gia_mac_dinh',
        'img',
        'trang_thai',
        'last_checked_at',
    ];

    protected $casts = [
        'gia_mac_dinh' => 'decimal:2',
        'last_checked_at' => 'datetime',
    ];

    // Relationships
    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class, 'loai_phong_id');
    }

    public function tang()
    {
        return $this->belongsTo(Tang::class, 'tang_id');
    }

    public function tienNghis()
    {
        return $this->belongsToMany(TienNghi::class, 'phong_tien_nghi');
    }

    public function phongDaDats()
    {
        return $this->hasMany(PhongDaDat::class);
    }

    public function wishlists()
    {
        return $this->hasMany(\App\Models\Wishlist::class, 'phong_id');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'wishlists', 'phong_id', 'user_id');
    }

    public function images()
    {
        return $this->hasMany(PhongImage::class, 'phong_id')->orderBy('id', 'asc');
    }

    public function firstImagePath()
    {
        $img = $this->images->first();
        return $img ? $img->image_path : null;
    }
}
