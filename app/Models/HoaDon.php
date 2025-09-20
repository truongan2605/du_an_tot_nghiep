<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoaDon extends Model
{
    use HasFactory;

    protected $table = 'hoa_don';

    protected $fillable = [
        'dat_phong_id',
        'so_hoa_don',
        'tong_thuc_thu',
        'don_vi',
        'trang_thai',
    ];

    protected $casts = [
        'tong_thuc_thu' => 'decimal:2',
    ];

    // Relationships
    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    // Scopes
    public function scopeDaXuat($query)
    {
        return $query->where('trang_thai', 'da_xuat');
    }

    public function scopeDaThanhToan($query)
    {
        return $query->where('trang_thai', 'da_thanh_toan');
    }
}
