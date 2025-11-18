<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoaDonItem extends Model
{
    use HasFactory;

    protected $table = 'hoa_don_items';

    protected $fillable = [
        'hoa_don_id',
        'type',
        'ref_id',
        'vat_dung_id',
        'phong_id',        // <- thêm
        'loai_phong_id',   // <- thêm
        'name',
        'quantity',
        'unit_price',
        'amount',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'phong_id' => 'integer',
        'loai_phong_id' => 'integer',
        'ref_id' => 'integer',
        'vat_dung_id' => 'integer',
    ];

    public function hoaDon()
    {
        return $this->belongsTo(\App\Models\HoaDon::class, 'hoa_don_id');
    }

    public function phong()
    {
        return $this->belongsTo(\App\Models\Phong::class, 'phong_id');
    }

    public function loaiPhong()
    {
        return $this->belongsTo(\App\Models\LoaiPhong::class, 'loai_phong_id');
    }

    public function vatDung()
    {
        return $this->belongsTo(\App\Models\VatDung::class, 'vat_dung_id');
    }
}
