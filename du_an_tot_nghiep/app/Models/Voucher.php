<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'voucher';

    protected $fillable = [
        'code',
        'type',
        'value',
        'qty',
        'start_date',
        'end_date',
        'min_order_amount',
        'applicable_to',
        'note',
        'usage_limit_per_user',
        'active'
    ];
}
