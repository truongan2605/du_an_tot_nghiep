<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiuPhong extends Model
{
    use HasFactory;

    protected $table = 'giu_phong';

    protected $fillable = [
        'dat_phong_id',
        'loai_phong_id',
        'phong_id',
        'so_luong',
        'het_han_luc',
        'released',
        'released_at',
        'released_by',
    ];

    protected $casts = [
        'het_han_luc' => 'datetime',
        'released' => 'boolean',
        'released_at' => 'datetime',
    ];

    // Relationships
    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class);
    }
    
    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }
    
    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    // Scopes
    public function scopeReleased($query)
    {
        return $query->where('released', true);
    }

    public function scopeNotReleased($query)
    {
        return $query->where('released', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('het_han_luc', '<', now());
    }
}
