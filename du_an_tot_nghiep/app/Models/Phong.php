<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Phong extends Model
{
    use HasFactory;

    protected $table = 'phong';

    protected $fillable = [
        'ma_phong',
        'name',
        'mo_ta', 
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

    public function getTongGiaAttribute()
    {
        if (!is_null($this->gia_mac_dinh) && $this->gia_mac_dinh > 0) {
            return (float) $this->gia_mac_dinh;
        }

        $base = 0;
        if ($this->loaiPhong) {
            $base = (float) ($this->loaiPhong->gia_mac_dinh ?? 0);
        }

        $typeAmenityIds = $this->loaiPhong ? $this->loaiPhong->tienNghis->pluck('id')->toArray() : [];

        $roomAmenityIds = $this->tienNghis ? $this->tienNghis->pluck('id')->toArray() : [];

        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        $amenitiesSum = 0;
        if (!empty($allAmenityIds)) {
            $amenitiesSum = (float) TienNghi::whereIn('id', $allAmenityIds)->sum('gia');
        }

        return $base + $amenitiesSum;
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

    public function firstImageUrl()
    {
        $path = $this->firstImagePath();
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }

        return asset('template/stackbros/assets/images/category/hotel/01.jpg');
    }
}
