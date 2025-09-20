<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongDaDat extends Model
{
    use HasFactory;

    protected $table = 'phong_da_dat';

    protected $fillable = [
        'dat_phong_item_id',
        'phong_id',
        'trang_thai',
        'checkin_datetime',
        'checkout_datetime',
        'thuc_te_nhan_phong_luc',
        'thuc_te_tra_phong_luc',
    ];

    protected $casts = [
        'checkin_datetime' => 'datetime',
        'checkout_datetime' => 'datetime',
        'thuc_te_nhan_phong_luc' => 'datetime',
        'thuc_te_tra_phong_luc' => 'datetime',
    ];

    // Relationships
    public function datPhongItem()
    {
        return $this->belongsTo(DatPhongItem::class);
    }

    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }

    // Scopes
    public function scopeDaDat($query)
    {
        return $query->where('trang_thai', 'da_dat');
    }

    public function scopeDangSuDung($query)
    {
        return $query->where('trang_thai', 'dang_su_dung');
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
