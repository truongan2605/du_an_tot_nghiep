<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoanTien extends Model
{
    use HasFactory;

    protected $table = 'hoan_tien';

    protected $fillable = [
        'giao_dich_id',
        'so_tien',
        'provider_ref',
        'trang_thai',
        'ly_do',
    ];

    protected $casts = [
        'so_tien' => 'decimal:2',
    ];

    // Relationships
    public function giaoDich()
    {
        return $this->belongsTo(GiaoDich::class);
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
