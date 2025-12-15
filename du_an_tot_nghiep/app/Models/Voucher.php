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
        'points_required'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
        'points_required' => 'integer',
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
        $candidates = ['value', 'discount', 'amount', 'discount_value', 'gia_tri', 'giatri'];

        $val = null;
        foreach ($candidates as $k) {
            if (array_key_exists($k, $this->attributes) && $this->attributes[$k] !== null && $this->attributes[$k] !== '') {
                $val = $this->attributes[$k];
                break;
            }
        }

        if ($val === null && isset($this->value) && $this->value !== null && $this->value !== '') {
            $val = $this->value;
        }

        if ($val === null || $val === '') {
            return '-';
        }

        $num = (float) $val;

        $typeRaw = $this->type ?? ($this->attributes['type'] ?? null) ?? ($this->discount_type ?? null);
        $type = strtolower(str_replace([' ', '_', '-'], '', (string) $typeRaw));

        $isPercent = in_array($type, ['phantram', 'percent', 'phan_tram', '%', 'percento', 'percentdiscount']);

        if ($isPercent) {
            if (fmod($num, 1) === 0.0) {
                return (int)$num . '%';
            }
            return rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',') . '%';
        }

        if (fmod($num, 1) === 0.0) {
            return number_format((int)$num, 0, ',', '.') . 'đ';
        }

        return number_format($num, 0, ',', '.') . 'đ';
    }

    public function getDisplayValueAttribute(): string
    {
        return $this->gia_tri_hien_thi;
    }
}
