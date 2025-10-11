<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BedType extends Model
{
    protected $fillable = ['name','slug','description','capacity','price','icon'];

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
