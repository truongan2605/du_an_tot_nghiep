<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatPhongItem extends Model
{
    use HasFactory;

    protected $table = 'dat_phong_item';

    protected $fillable = [
        'dat_phong_id',
        'loai_phong_id',
        'so_luong',
        'gia_tren_dem',
        'so_dem',
        'taxes_amount',
    ];

    protected $casts = [
        'gia_tren_dem' => 'decimal:2',
        'taxes_amount' => 'decimal:2',
    ];

    // Relationships
   public function datPhong()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }

    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class, 'loai_phong_id');
    }

    public function phongDaDats()
    {
        return $this->hasMany(PhongDaDat::class, 'dat_phong_item_id');
    }

    // Accessors
    public function getTongTienAttribute()
    {
        return $this->gia_tren_dem * $this->so_dem * $this->so_luong;
    }
}
