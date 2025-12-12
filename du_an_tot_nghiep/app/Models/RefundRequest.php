<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $fillable = [
        'dat_phong_id',
        'dat_phong_item_id',  // Liên kết với phòng cụ thể bị hủy
        'refund_type',        // 'full_booking' hoặc 'single_room'
        'amount',
        'percentage',
        'status',
        'requested_at',
        'processed_at',
        'admin_note',
        'processed_by',
        'proof_image_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'integer',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function datPhong(): BelongsTo
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }

    /**
     * Liên kết với phòng cụ thể bị hủy (nullable - null nếu hủy toàn bộ booking)
     */
    public function datPhongItem(): BelongsTo
    {
        return $this->belongsTo(DatPhongItem::class, 'dat_phong_item_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'approved' => 'bg-info',
            'completed' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xử lý',
            'approved' => 'Đã duyệt',
            'completed' => 'Hoàn tiền',
            'rejected' => 'Từ chối',
            default => $this->status
        };
    }

    public function getProofImageUrlAttribute(): ?string
    {
        return $this->proof_image_path 
            ? asset('storage/' . $this->proof_image_path) 
            : null;
    }
}
