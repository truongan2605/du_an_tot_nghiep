<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'dat_phong_id',
        'old_room_id',
        'new_room_id',
        'old_price',
        'new_price',
        'price_difference',
        'nights',
        'change_reason',
        'changed_by_type',
        'changed_by_user_id',
        'status',
        'payment_info',
    ];

    protected $casts = [
        'payment_info' => 'array',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'nights' => 'integer',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }

    public function oldRoom()
    {
        return $this->belongsTo(Phong::class, 'old_room_id');
    }

    public function newRoom()
    {
        return $this->belongsTo(Phong::class, 'new_room_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    // Helper methods
    public function isUpgrade()
    {
        return $this->price_difference > 0;
    }

    public function isDowngrade()
    {
        return $this->price_difference < 0;
    }

    public function isSamePrice()
    {
        return $this->price_difference == 0;
    }
}
