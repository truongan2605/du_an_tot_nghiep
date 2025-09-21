<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongTienNghi extends Model
{
    use HasFactory;

    protected $table = 'phong_tien_nghi';

    protected $fillable = [
        'phong_id',
        'tien_nghi_id',
    ];

    // Relationships
    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }

    public function tienNghi()
    {
        return $this->belongsTo(TienNghi::class);
    }
}
