<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{   
    use Auditable;
    protected $table = 'wishlists';

    protected $fillable = [
        'user_id',
        'phong_id',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function phong()
    {
        return $this->belongsTo(\App\Models\Phong::class, 'phong_id', 'id');
    }
}
