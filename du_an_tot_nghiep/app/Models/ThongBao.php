<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThongBao extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'thong_bao';

    protected $fillable = [
        'nguoi_nhan_id',
        'kenh',
        'ten_template',
        'payload',
        'trang_thai',
        'so_lan_thu',
        'lan_thu_cuoi',
        'batch_id',
        'error_message',
    ];

    protected $casts = [
        'lan_thu_cuoi' => 'datetime',
    ];

    // Custom accessor for payload to handle both string and array
    public function getPayloadAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }

    // Custom mutator for payload to always store as JSON string
    public function setPayloadAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['payload'] = json_encode($value);
        } else {
            $this->attributes['payload'] = $value;
        }
    }

    // Relationships
    public function nguoiNhan()
    {
        return $this->belongsTo(User::class, 'nguoi_nhan_id');
    }
}
