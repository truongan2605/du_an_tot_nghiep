<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongImage extends Model
{
    use HasFactory;
    use Auditable;

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
