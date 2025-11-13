<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatDungIncident extends Model
{
    use HasFactory;

    protected $table = 'vat_dung_incidents';

    protected $fillable = [
        'phong_vat_dung_instance_id',
        'phong_id',
        'dat_phong_id',
        'vat_dung_id',
        'type',
        'description',
        'fee',
        'reported_by',
        'billed_at'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'billed_at' => 'datetime',
    ];



    public function instance()
    {
        return $this->belongsTo(PhongVatDungInstance::class, 'phong_vat_dung_instance_id');
    }

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }

    public function vatDung()
    {
        return $this->belongsTo(VatDung::class, 'vat_dung_id');
    }

    public function reporter()
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by');
    }
}
