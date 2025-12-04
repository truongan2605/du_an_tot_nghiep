<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatPhongItem2 extends Model
{
    protected $table = 'dat_phong_items';

    protected $fillable = [
        'dat_phong_id',
        'phong_id',
        'gia',
        'so_luong'
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }
}
