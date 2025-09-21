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
        'name',
        'price',
        'qty',
        'total_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    // Accessors
    public function getTotalPriceAttribute()
    {
        return $this->price * $this->qty;
    }
}
