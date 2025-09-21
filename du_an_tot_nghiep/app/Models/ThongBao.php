<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThongBao extends Model
{
    use HasFactory;

    protected $table = 'thong_bao';

    protected $fillable = [
        'nguoi_nhan_id',
        'kenh',
        'ten_template',
        'payload',
        'trang_thai',
        'so_lan_thu',
        'lan_thu_cuoi',
    ];

    protected $casts = [
        'payload' => 'array',
        'lan_thu_cuoi' => 'datetime',
    ];

    // Relationships
    public function nguoiNhan()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_nhan_id');
    }
}
