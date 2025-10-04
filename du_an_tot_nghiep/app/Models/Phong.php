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
public function images()
{
    // lấy theo id tăng dần = thứ tự thêm ảnh
    return $this->hasMany(PhongImage::class, 'phong_id')->orderBy('id', 'asc');
}

// tiện helper lấy ảnh đầu tiên
public function firstImagePath()
{
    $img = $this->images->first();
    return $img ? $img->image_path : null;
}

public function getTongGiaAttribute()
{
    $tong = 0;

    // Giá mặc định của phòng
    $tong += $this->gia_mac_dinh ?? 0;

    // Giá loại phòng (nếu có cột 'gia')
    if ($this->loaiPhong && isset($this->loaiPhong->gia)) {
        $tong += $this->loaiPhong->gia;
    }

    // Tiện nghi của phòng (bổ sung)
    if ($this->tienNghis) {
        $tong += $this->tienNghis->sum('gia');
    }

    // Nếu loại phòng cũng có tiện nghi riêng → cộng thêm
    if ($this->loaiPhong && method_exists($this->loaiPhong, 'tienNghis')) {
        $tong += $this->loaiPhong->tienNghis->sum('gia');
    }

    return $tong;
}


}
