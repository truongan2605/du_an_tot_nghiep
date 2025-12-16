<?php

namespace App\Models;

use App\Traits\Auditable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use Auditable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'so_dien_thoai',
        'phong_ban',
        'vai_tro',
        'is_active',
        'is_disabled', // Thêm mới: true = bị admin vô hiệu hóa
        'country',
        'dob',
        'gender',
        'address',
        'avatar',
        'provider',
        'provider_id',
        'member_level',
        'total_spent',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_disabled' => 'boolean', // Thêm mới
        'dob' => 'date',
        'total_spent' => 'decimal:2',
        'member_level' => 'string',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ======= Vai trò =======
    public function isAdmin(): bool
    {
        return $this->vai_tro === 'admin';
    }

    // ======= Quan hệ =======
    public function datPhongs()
    {
        return $this->hasMany(DatPhong::class, 'nguoi_dung_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function favoritePhongs()
    {
        return $this->belongsToMany(Phong::class, 'wishlists', 'user_id', 'phong_id')
                    ->withTimestamps();
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }

    // ======= Quan hệ voucher =======
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'user_voucher', 'user_id', 'voucher_id')
                    ->withPivot('claimed_at')
                    ->withTimestamps();
    }

    // ======= Hệ thống hạng thành viên (Loyalty Program) =======
    
    /**
     * Lấy discount percentage theo hạng thành viên
     */
    public function getMemberDiscountPercent(): float
    {
        return match($this->member_level ?? 'dong') {
            'dong' => 3.0,
            'bac' => 5.0,
            'vang' => 10.0,
            'kim_cuong' => 15.0,
            default => 0.0,
        };
    }

    /**
     * Lấy tên hạng thành viên (tiếng Việt)
     */
    public function getMemberLevelName(): string
    {
        return match($this->member_level ?? 'dong') {
            'dong' => 'Đồng',
            'bac' => 'Bạc',
            'vang' => 'Vàng',
            'kim_cuong' => 'Kim Cương',
            default => 'Đồng',
        };
    }

    /**
     * Tính toán hạng thành viên dựa trên total_spent
     * @return string Hạng thành viên mới
     */
    public function calculateMemberLevel(): string
    {
        $totalSpent = (float) ($this->total_spent ?? 0);

        // Kiểm tra đơn đơn lẻ >= 1.000.000đ để lên Bạc
        $hasSingleOrderOver1M = $this->datPhongs()
            ->where('trang_thai', 'hoan_thanh')
            ->where('tong_tien', '>=', 1000000)
            ->exists();

        if ($totalSpent >= 50000000) {
            return 'kim_cuong';
        } elseif ($totalSpent >= 15000000) {
            return 'vang';
        } elseif ($hasSingleOrderOver1M || $totalSpent >= 1000000) {
            return 'bac';
        } else {
            return 'dong';
        }
    }

    /**
     * Cập nhật hạng thành viên dựa trên total_spent hiện tại
     */
    public function updateMemberLevel(): bool
    {
        $newLevel = $this->calculateMemberLevel();
        if ($this->member_level !== $newLevel) {
            $this->member_level = $newLevel;
            return $this->save();
        }
        return false;
    }

    /**
     * Tính tổng chi tiêu từ các booking đã hoàn thành
     */
    public function calculateTotalSpent(): float
    {
        return (float) $this->datPhongs()
            ->where('trang_thai', 'hoan_thanh')
            ->sum('tong_tien');
    }

    /**
     * Cập nhật total_spent và hạng thành viên
     */
    public function refreshLoyaltyStatus(): void
    {
        $newTotalSpent = $this->calculateTotalSpent();
        $newLevel = $this->calculateMemberLevel();
        
        // Chỉ update nếu có thay đổi
        $needsUpdate = false;
        if ((float) ($this->total_spent ?? 0) != $newTotalSpent) {
            $this->total_spent = $newTotalSpent;
            $needsUpdate = true;
        }
        if (($this->member_level ?? 'dong') !== $newLevel) {
            $this->member_level = $newLevel;
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            $this->save();
        }
    }

    /**
     * Lấy thông tin hạng tiếp theo
     */
    public function getNextLevelInfo(): array
    {
        $currentLevel = $this->member_level ?? 'dong';
        $totalSpent = (float) ($this->total_spent ?? 0);

        return match($currentLevel) {
            'dong' => [
                'name' => 'Bạc',
                'required' => 1000000,
                'current' => $totalSpent,
                'remaining' => max(0, 1000000 - $totalSpent),
                'discount' => 5.0,
            ],
            'bac' => [
                'name' => 'Vàng',
                'required' => 15000000,
                'current' => $totalSpent,
                'remaining' => max(0, 15000000 - $totalSpent),
                'discount' => 10.0,
            ],
            'vang' => [
                'name' => 'Kim Cương',
                'required' => 50000000,
                'current' => $totalSpent,
                'remaining' => max(0, 50000000 - $totalSpent),
                'discount' => 15.0,
            ],
            'kim_cuong' => [
                'name' => null,
                'required' => null,
                'current' => $totalSpent,
                'remaining' => 0,
                'discount' => 15.0,
            ],
            default => [
                'name' => 'Bạc',
                'required' => 1000000,
                'current' => $totalSpent,
                'remaining' => max(0, 1000000 - $totalSpent),
                'discount' => 5.0,
            ],
        };
    }
}
