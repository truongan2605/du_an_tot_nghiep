<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoaDonItem extends Model
{
    use HasFactory;

    protected $table = 'hoa_don_items';

    protected $fillable = [
        'hoa_don_id',
        'type',
        'ref_id',
        'vat_dung_id',
        'name',
        'quantity',
        'unit_price',
        'amount',
        'note'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];
}
