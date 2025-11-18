<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanhGiaSpace extends Model
{
    use HasFactory;

    protected $table = 'danh_gia_space';

    protected $fillable = [
        'phong_id',
        'user_id',
        'dat_phong_id',
        'rating',
        'noi_dung',
        'parent_id',
        'is_new',
        'status',
    ];
public function datPhong()
{
    return $this->belongsTo(DatPhong::class, 'dat_phong_id');
}

public function phong()
{
    return $this->belongsTo(Phong::class, 'phong_id');
}



    public function user()
    {
        return $this->belongsTo(User::class);
    }

   

    public function replies()
    {
        return $this->hasMany(DanhGiaSpace::class, 'parent_id');
    }
    
}
