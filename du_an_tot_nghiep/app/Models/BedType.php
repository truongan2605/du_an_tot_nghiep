<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BedType extends Model
{
    use HasFactory;

    protected $table = 'bed_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'capacity',
        'price',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function phongs()
    {
        return $this->belongsToMany(Phong::class, 'phong_bed')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
