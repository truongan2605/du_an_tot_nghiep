<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVoucher extends Model
{
    use HasFactory;

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
