<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatDung extends Model
{
    use HasFactory;
    use Auditable;

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
        return $this->belongsToMany(Phong::class, 'phong_vat_dung')
            ->using(\App\Models\PhongVatDung::class)
            ->withPivot(['so_luong', 'da_tieu_thu', 'gia_override', 'tracked_instances'])
            ->withTimestamps();
    }


    public function loaiPhongs()
    {
        return $this->belongsToMany(
            \App\Models\LoaiPhong::class,
            'loai_phong_vat_dung',
            'vat_dung_id',
            'loai_phong_id'
        )
            ->withPivot(['so_luong'])
            ->withTimestamps()
            ->orderByDesc('loai_phong.id');
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
