<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoaiPhongTienNghi extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'loai_phong_tien_nghi';

    protected $fillable = [
        'loai_phong_id',
        'tien_nghi_id',
    ];

    // Relationships
    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class);
    }

    public function tienNghi()
    {
        return $this->belongsTo(TienNghi::class);
    }
}
