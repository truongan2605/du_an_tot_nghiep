<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatPhong extends Model
{
    use HasFactory;

    protected $table = 'dat_phong';

    protected $fillable = [
        'ma_tham_chieu',
        'nguoi_dung_id',
        'trang_thai',
        'ngay_nhan_phong',
        'ngay_tra_phong',
        'so_khach',
        'tong_tien',
        'don_vi_tien',
        'can_thanh_toan',
        'created_by',
        'phuong_thuc',
        'ma_voucher',
        'discount_amount',
        'snapshot_total',
        'snapshot_meta',
        'source',
        'ghi_chu',
    ];

    protected $casts = [
        'ngay_nhan_phong' => 'date',
        'ngay_tra_phong' => 'date',
        'so_khach' => 'integer',
        'tong_tien' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'snapshot_total' => 'decimal:2',
        'snapshot_meta' => 'array',
        'can_thanh_toan' => 'boolean',
    ];

    // Relationships
    public function nguoiDung()
    {
        return $this->belongsTo(User::class, 'nguoi_dung_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function datPhongItems()
    {
        return $this->hasMany(DatPhongItem::class);
    }

    public function datPhongAddons()
    {
        return $this->hasMany(DatPhongAddon::class);
    }

    public function giaoDichs()
    {
        return $this->hasMany(GiaoDich::class);
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class);
    }

    public function giuPhongs()
    {
        return $this->hasMany(GiuPhong::class);
    }

    public function hoaDons()
    {
        return $this->hasMany(HoaDon::class);
    }

    public function voucherUsages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // Scopes
    public function scopeDangCho($query)
    {
        return $query->where('trang_thai', 'dang_cho');
    }

    public function scopeDaXacNhan($query)
    {
        return $query->where('trang_thai', 'da_xac_nhan');
    }

    public function scopeDaNhanPhong($query)
    {
        return $query->where('trang_thai', 'da_nhan_phong');
    }

    public function scopeHoanThanh($query)
    {
        return $query->where('trang_thai', 'hoan_thanh');
    }

    public function scopeDaHuy($query)
    {
        return $query->where('trang_thai', 'da_huy');
    }
}
