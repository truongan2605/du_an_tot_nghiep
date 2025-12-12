<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserVoucher extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'user_voucher';

    protected $fillable = [
        'user_id',
        'voucher_id',
        'claimed_at',
    ];

    protected $dates = [
        'claimed_at',
    ];

    // ======= Quan hệ tới User =======
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ======= Quan hệ tới Voucher =======
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}
