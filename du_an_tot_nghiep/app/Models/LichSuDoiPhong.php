<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuDoiPhong extends Model
{
    protected $table = 'lich_su_doi_phong';

    protected $fillable = [
        'dat_phong_id',
        'dat_phong_item_id',
        'phong_cu_id',
        'phong_moi_id',
        'gia_cu',
        'gia_moi',
        'so_dem',
        'loai',
        'nguoi_thuc_hien',
    ];

    public function phongCu()
    {
        return $this->belongsTo(Phong::class, 'phong_cu_id');
    }

    public function phongMoi()
    {
        return $this->belongsTo(Phong::class, 'phong_moi_id');
    }
}
