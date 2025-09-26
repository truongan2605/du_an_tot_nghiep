<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DanhGia extends Model
{
    use HasFactory;

    protected $table = 'danh_gia';

    protected $fillable = [
        'dat_phong_id',
        'nguoi_dung_id',
        'diem',
        'noi_dung',
        'anh',
        'trang_thai_kiem_duyet',
    ];

    protected $casts = [
        'anh' => 'array',
    ];

    // Relationships
    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    public function nguoiDung()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeDaDang($query)
    {
        return $query->where('trang_thai_kiem_duyet', 'da_dang');
    }

    public function scopeChoKiemDuyet($query)
    {
        return $query->where('trang_thai_kiem_duyet', 'cho_kiem_duyet');
    }
}
