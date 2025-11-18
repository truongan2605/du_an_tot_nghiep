<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'voucher';

    protected $fillable = [
        'name',
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
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    // ======= Quan hệ tới User =======
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_voucher', 'voucher_id', 'user_id')
                    ->withPivot('claimed_at')
                    ->withTimestamps();
    }

    // ======= Kiểm tra còn hiệu lực =======
    public function getConHieuLucAttribute(): bool
    {
        $today = Carbon::today();
        return $this->active && $this->start_date <= $today && $this->end_date >= $today;
    }

    // ======= Hiển thị giá trị voucher (đẹp hơn) =======
    public function getGiaTriHienThiAttribute(): string
    {
        if ($this->type === 'phan_tram') {
            return $this->value . '%';
        }
        return number_format($this->value, 0, ',', '.') . 'đ';
    }
}
