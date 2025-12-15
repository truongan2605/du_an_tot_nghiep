<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatPhongItem extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'dat_phong_item';

    protected $fillable = [
        'dat_phong_id',
        'phong_id',
        'loai_phong_id',
        'so_luong',
        'so_nguoi_o',
        'number_child',
        'number_adult',
        'gia_tren_dem',
        'so_dem',
        'taxes_amount',
        'tong_item',
        'voucher_allocated',  // Số tiền voucher được giảm cho phòng này
        'spec_signature_hash',
        // Hỗ trợ hủy từng phòng
        'trang_thai',
        'refund_amount',
        'refund_percentage',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'so_luong' => 'integer',
        'gia_tren_dem' => 'decimal:2',
        'so_dem' => 'integer',
        'taxes_amount' => 'decimal:2',
    ];

    // Relationships
  
    protected $attributes = [
        'so_dem' => 1,
    ];

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }

    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class, 'loai_phong_id');
    }

     public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function phongDaDats()
    {
        return $this->hasMany(PhongDaDat::class, 'dat_phong_item_id');
    }

    /**
     * Liên kết với yêu cầu hoàn tiền (nếu phòng này bị hủy)
     */
    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class, 'dat_phong_item_id');
    }

    // Accessors
    public function getTongItemAttribute()
    {
        if (!is_null($this->attributes['tong_item'] ?? null)) {
            return (float) $this->attributes['tong_item'];
        }
        return (float) ($this->gia_tren_dem ?? 1) * (int) ($this->so_dem ?? 1) * (int) ($this->so_luong ?? 1);
    }

    public function getTongTienAttribute()
    {
        return $this->getTongItemAttribute();
    }
}
