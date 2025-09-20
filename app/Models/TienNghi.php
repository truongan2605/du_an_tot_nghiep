<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TienNghi extends Model
{
    use HasFactory;

    protected $table = 'tien_nghi';

    protected $fillable = [
        'ten',
        'mo_ta',
        'icon',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
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
