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
    public function scopeTrongKhoangThoiGian($query, string $tuNgay, string $denNgay)
    {
        $trangThaiGiuPhong = ['da_xac_nhan', 'dang_o']; // điều chỉnh theo hệ thống của bạn

        return $query->whereNotExists(function ($q) use ($tuNgay, $denNgay, $trangThaiGiuPhong) {
            $q->selectRaw(1)
              ->from('dat_phongs as dp')
              ->whereColumn('dp.phong_id', 'phongs.id')
              ->whereIn('dp.trang_thai', $trangThaiGiuPhong)
              ->where('dp.ngay_nhan', '<', $denNgay)
              ->where('dp.ngay_tra', '>', $tuNgay);
        });
    }

    /**
     * Áp dụng các bộ lọc: số khách, loại phòng, khoảng giá.
     */
    public function scopePhuHopBoLoc($query, ?int $soKhach, ?int $loaiPhongId, ?int $giaTu, ?int $giaDen)
    {
        if (!is_null($soKhach)) {
            $query->where('so_nguoi_toi_da', '>=', $soKhach);
        }
        if (!is_null($loaiPhongId)) {
            $query->where('loai_phong_id', $loaiPhongId);
        }
        if (!is_null($giaTu)) {
            $query->where('gia_theo_dem', '>=', $giaTu);
        }
        if (!is_null($giaDen)) {
            $query->where('gia_theo_dem', '<=', $giaDen);
        }
        return $query;
    }
    public function scopeTheoTenLoaiPhong($query, ?string $ten)
{
    if ($ten === null || $ten === '') return $query;

    // Nếu cột tên loại của bạn là 'ten' thì đổi lại cho đúng
    return $query->whereHas('loaiPhong', function ($q) use ($ten) {
        $q->where('ten_loai', 'LIKE', '%' . $ten . '%');
    });
}
    public function scopeTuKhoa($query, ?string $text)
{
    $text = trim((string)$text);
    if ($text === '') return $query;

    return $query->where(function ($q) use ($text) {
        // Tìm trong các cột của bảng phòng (đổi tên cột cho đúng dự án)
        $q->where('ten', 'LIKE', '%'.$text.'%')
          ->orWhere('ma_phong', 'LIKE', '%'.$text.'%')
          ->orWhere('mo_ta', 'LIKE', '%'.$text.'%')
          // Tìm theo tên loại phòng
          ->orWhereHas('loaiPhong', function($lp) use ($text){
              $lp->where('ten_loai', 'LIKE', '%'.$text.'%'); // nếu cột là 'ten' thì đổi lại
          });
    });
}

}
