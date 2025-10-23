<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BedType extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','description','capacity','price','icon'];

    protected $casts = [
        'capacity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function loaiPhongs()
    {
        return $this->belongsToMany(LoaiPhong::class, 'loai_phong_bed_type')
            ->withPivot(['quantity','price'])
            ->withTimestamps();
    }

    public function phongs()
    {
        return $this->belongsToMany(Phong::class, 'phong_bed_type')
            ->withPivot(['quantity','price'])
            ->withTimestamps();
    }
}
