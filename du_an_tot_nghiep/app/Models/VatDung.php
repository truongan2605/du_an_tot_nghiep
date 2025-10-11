<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatDung extends Model
{
    use HasFactory;

    protected $table = 'vat_dungs';

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
        return $this->belongsToMany(Phong::class, 'phong_vat_dung');
    }

   


public function loaiPhongs()
{
    return $this->belongsToMany(LoaiPhong::class, 'loai_phong_vat_dung', 'vat_dung_id', 'loai_phong_id');
}

}