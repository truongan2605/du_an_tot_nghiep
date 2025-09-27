<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhongImage extends Model
{
    use HasFactory;

    protected $table = 'phong_images';

    protected $fillable = [
        'phong_id',
        'image_path',
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class, 'phong_id');
    }
}
