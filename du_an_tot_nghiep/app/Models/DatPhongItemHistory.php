<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatPhongItemHistory extends Model
{
    protected $table = 'dat_phong_items_history';

 protected $fillable = [
        'dat_phong_id',
        'phong_id',
        'phong_ma',
        'loai_phong_id',
        'gia_tren_dem',
        'so_luong',
        'snapshot',
        'created_at',
        'updated_at',
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }
}
