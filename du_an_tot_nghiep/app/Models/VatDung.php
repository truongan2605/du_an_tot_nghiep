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
        'loai',
        'tracked_instances',
    ];


    protected $casts = [
        'active' => 'boolean',
        'gia' => 'decimal:2',
        'tracked_instances' => 'boolean',
    ];

    public const LOAI_DO_AN = 'do_an';
    public const LOAI_DO_DUNG = 'do_dung';

    public function isConsumable(): bool
    {
        return $this->loai === self::LOAI_DO_AN;
    }

    public function isDurable(): bool
    {
        return $this->loai === self::LOAI_DO_DUNG;
    }

    // Relationships
    public function phongs()
    {
        return $this->belongsToMany(Phong::class, 'phong_vat_dung');
    }

    public function loaiPhongs()
    {
        return $this->belongsToMany(LoaiPhong::class, 'loai_phong_vat_dung', 'vat_dung_id', 'loai_phong_id');
    }

    public function instances()
    {
        return $this->hasMany(\App\Models\PhongVatDungInstance::class, 'vat_dung_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}