<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;
    use Auditable;

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

    // ======= Quan hệ tới VoucherUsage (để check đã dùng chưa) =======
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class, 'voucher_id');
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
