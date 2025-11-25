<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRank extends Model
{
    protected $fillable = ['user_id', 'total_amount', 'rank'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRankLabelAttribute(): string
    {
        return match ($this->rank) {
            'bac'       => 'Bạc',
            'vang'      => 'Vàng',
            'kim_cuong' => 'Kim cương',
            default     => 'Chưa có hạng',
        };
    }
}
