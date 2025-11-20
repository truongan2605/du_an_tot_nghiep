<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TienNghi extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'tien_nghi';

    protected $fillable = [
        'ten',
        'mo_ta',
        'icon',
        'active',
        'gia',  
    ];


    protected $casts = [
        'active' => 'boolean',
        'gia' => 'decimal:2',
    ];


    // Relationships
    public function phongs()
    {
        return $this->belongsToMany(Phong::class, 'phong_tien_nghi');
    }

    public function loaiPhongs()
    {
        return $this->belongsToMany(LoaiPhong::class, 'loai_phong_tien_nghi');
    }
}
