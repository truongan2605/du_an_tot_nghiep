<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatPhongAddon extends Model
{
    use HasFactory;

    protected $table = 'dat_phong_addon';

    protected $fillable = [
        'dat_phong_id',
        'phong_id',
        'name',
        'price',
        'qty',
        'total_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'qty' => 'integer',
        'total_price' => 'decimal:2',
    ];

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    public function getTotalPriceAttribute()
    {
        return (float)$this->price * (int)$this->qty;
    }
}
