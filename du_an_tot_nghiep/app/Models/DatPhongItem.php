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
        'phong_id',
        'loai_phong_id',
        'so_luong',
        'gia_tren_dem',
        'so_dem',
        'taxes_amount',
        'tong_item'
    ];

    protected $casts = [
        'so_luong' => 'integer',
        'gia_tren_dem' => 'decimal:2',
        'so_dem' => 'integer',
        'taxes_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'so_dem' => 1,
    ];

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class);
    }

    public function phongDaDats()
    {
        return $this->hasMany(PhongDaDat::class);
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
