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
        public function getConHieuLucAttribute()
    {
        $today = now()->toDateString();
        return $this->active && $this->start_date <= $today && $this->end_date >= $today;
    }

    // Format giá trị hiển thị
    public function getGiaTriHienThiAttribute()
    {
        return $this->type === 'phan_tram'
            ? $this->value . '%'
            : number_format($this->gia_tri, 0, ',', '.') . 'đ';
    }
}
