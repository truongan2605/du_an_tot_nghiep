<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoucherUsage extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'voucher_usage';

    protected $fillable = [
        'voucher_id',
        'dat_phong_id',
        'nguoi_dung_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function datPhong()
    {
        return $this->belongsTo(DatPhong::class);
    }

    public function nguoiDung()
    {
        return $this->belongsTo(User::class);
    }
    public function booking()
    {
        return $this->belongsTo(DatPhong::class, 'dat_phong_id');
    }
}
