<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongVatDungInstance extends Model
{
    use HasFactory;

    protected $table = 'phong_vat_dung_instances';

    public const STATUS_PRESENT = 'present';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_MISSING = 'missing';
    public const STATUS_LOST = 'lost';
    public const STATUS_ARCHIVED = 'archived';

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_DAMAGED,
            self::STATUS_MISSING,
            self::STATUS_LOST,
            self::STATUS_ARCHIVED,
        ];
    }

    protected $fillable = [
        'phong_id',
        'vat_dung_id',
        'serial',
        'status',
        'note',
        'created_by',
        'quantity',
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }

    public function vatDung()
    {
        return $this->belongsTo(VatDung::class, 'vat_dung_id');
    }

    public function incidents()
    {
        return $this->hasMany(VatDungIncident::class, 'phong_vat_dung_instance_id');
    }
}
