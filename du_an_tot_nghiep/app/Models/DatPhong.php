<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DatPhong extends Model
{
    use HasFactory;

    protected $table = 'dat_phong';

    protected $fillable = [
        'ma_tham_chieu',
        'nguoi_dung_id',
        'trang_thai',
        'ngay_nhan_phong',
        'checked_in_at',
        'ngay_tra_phong',
        'so_khach',
        'tong_tien',
        'don_vi_tien',
        'can_thanh_toan',
        'created_by',
        'phuong_thuc',
        'ma_voucher',
        'voucher_code',
        'discount_amount',
        'snapshot_total',
        'snapshot_meta',
        'source',
        'ghi_chu',
        'can_xac_nhan',
        'contact_name',
        'contact_address',
        'contact_phone',
        'deposit_amount',
    ];


    protected $casts = [
        'ngay_nhan_phong' => 'date',
        'checked_in_at' => 'datetime',
        'ngay_tra_phong' => 'date',
        'so_khach' => 'integer',
        'tong_tien' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'snapshot_total' => 'decimal:2',
        'snapshot_meta' => 'array',
        'can_thanh_toan' => 'boolean',
        'can_xac_nhan' => 'boolean',
        'deposit_amount' => 'decimal:2',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_dung_id');
    }
    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'nguoi_dung_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'created_by');
    }

    public function datPhongItems()
    {
        return $this->hasMany(DatPhongItem::class, 'dat_phong_id');
    }

    public function datPhongAddons(): HasMany
    {
        return $this->hasMany(DatPhongAddon::class, 'dat_phong_id');
    }

    public function giaoDichs(): HasMany
    {
        return $this->hasMany(GiaoDich::class, 'dat_phong_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGia::class, 'dat_phong_id');
    }

    public function giuPhongs(): HasMany
    {
        return $this->hasMany(GiuPhong::class, 'dat_phong_id');
    }

    public function hoaDons(): HasMany
    {
        return $this->hasMany(HoaDon::class, 'dat_phong_id');
    }

    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class, 'dat_phong_id');
    }

        public function phongDaDats()
    {
        return $this->hasManyThrough(
            PhongDaDat::class,
            DatPhongItem::class,
            'dat_phong_id',
            'dat_phong_item_id',
            'id',
            'id'
        );
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

    public function scopeDaGanPhong($query)
    {
        return $query->where('trang_thai', 'da_gan_phong');
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
