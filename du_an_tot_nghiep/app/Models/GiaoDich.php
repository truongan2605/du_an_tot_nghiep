<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiaoDich extends Model
{
    use HasFactory;

    protected $table = 'giao_dich';

    protected $fillable = [
        'dat_phong_id',
        'nha_cung_cap',
        'provider_txn_ref',
        'so_tien',
        'don_vi',
        'trang_thai',
        'ghi_chu',
    ];

    protected $casts = [
        'so_tien' => 'decimal:2',
    ];

    // Relationships
  public function dat_phong()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id', 'id');
    }

    public function hoanTiens()
    {
        return $this->hasMany(HoanTien::class);
    }

    // Scopes
    public function scopeDangCho($query)
    {
        return $query->where('trang_thai', 'dang_cho');
    }

    public function scopeThanhCong($query)
    {
        return $query->where('trang_thai', 'thanh_cong');
    }

    public function scopeThatBai($query)
    {
        return $query->where('trang_thai', 'that_bai');
    }

    public function scopeDaHoan($query)
    {
        return $query->where('trang_thai', 'da_hoan');
    }
}
