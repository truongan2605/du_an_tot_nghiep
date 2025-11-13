<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongVatDungConsumption extends Model
{
    use HasFactory;

    protected $table = 'phong_vat_dung_consumptions';

    protected $fillable = [
        'dat_phong_id',
        'phong_id',
        'vat_dung_id',
        'quantity',
        'unit_price',
        'created_by',
        'note'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'consumed_at' => 'datetime',
        'billed_at' => 'datetime',
    ];

    public function datPhong()
    {
        return $this->belongsTo(\App\Models\DatPhong::class, 'dat_phong_id');
    }
    public function phong()
    {
        return $this->belongsTo(\App\Models\Phong::class, 'phong_id');
    }
    public function vatDung()
    {
        return $this->belongsTo(\App\Models\VatDung::class, 'vat_dung_id');
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
