<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;

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
        'active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    // Relationships
    public function voucherUsages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeValid($query)
    {
        return $query->where(function($q) {
            $q->whereNull('qty')
              ->orWhere('qty', '>', 0);
        });
    }
}
