<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Phong extends Model
{
    use HasFactory;

    protected $table = 'phong';

    protected $fillable = [
        'ma_phong',
        'name',
        'mo_ta',
        'loai_phong_id',
        'tang_id',
        'suc_chua',
        'so_giuong',
        'gia_mac_dinh',
        'gia_cuoi_cung',  
        'img',
        'trang_thai',
        'last_checked_at',
    ];

    protected $casts = [
        'gia_mac_dinh' => 'decimal:2',
        'gia_cuoi_cung' => 'decimal:2',
        'last_checked_at' => 'datetime',
    ];

    public function loaiPhong()
    {
        return $this->belongsTo(LoaiPhong::class, 'loai_phong_id');
    }

    public function tang()
    {
        return $this->belongsTo(Tang::class, 'tang_id');
    }

    public function tienNghis()
    {
        return $this->belongsToMany(TienNghi::class, 'phong_tien_nghi');
    }

    public function bedTypes()
    {
        return $this->belongsToMany(BedType::class, 'phong_bed_type')
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(PhongImage::class, 'phong_id')->orderBy('id', 'asc');
    }

    public function phongDaDats()
    {
        return $this->hasMany(PhongDaDat::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'phong_id');
    }

    public function getTongGiaAttribute()
    {
        if (!is_null($this->gia_cuoi_cung) && (float)$this->gia_cuoi_cung > 0) {
            return (float) $this->gia_cuoi_cung;
        }

        return $this->calculateGiaCuoiCung(false);
    }

    public function calculateGiaCuoiCung(bool $reloadRelations = true): float
    {
        if ($reloadRelations) {
            $this->loadMissing(['loaiPhong.tienNghis', 'tienNghis', 'bedTypes']);
        }

        $base = 0.0;
        if ($this->loaiPhong) {
            $base = (float) ($this->loaiPhong->gia_mac_dinh ?? 0);
        } else {
            $base = (float) ($this->gia_mac_dinh ?? 0);
        }

        $typeAmenityIds = $this->loaiPhong ? $this->loaiPhong->tienNghis->pluck('id')->toArray() : [];
        $roomAmenityIds = $this->tienNghis ? $this->tienNghis->pluck('id')->toArray() : [];
        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        $amenitiesSum = 0.0;
        if (!empty($allAmenityIds)) {
            $amenitiesSum = (float) TienNghi::whereIn('id', $allAmenityIds)->sum('gia');
        }

        $bedTotal = 0.0;
        $beds = $this->relationLoaded('bedTypes') ? $this->bedTypes : $this->bedTypes()->get();
        foreach ($beds as $bt) {
            $qty = (int) ($bt->pivot->quantity ?? 0);
            if ($qty <= 0) continue;
            $pricePer = $bt->pivot->price !== null ? (float) $bt->pivot->price : (float) ($bt->price ?? 0);
            $bedTotal += $qty * $pricePer;
        }

        $total = $base + $amenitiesSum + $bedTotal;

        return max(0.0, (float) $total);
    }

    public function recalcAndSave(bool $forceOverwrite = true)
    {
        $new = $this->calculateGiaCuoiCung(true);

        if (!$forceOverwrite && !is_null($this->gia_cuoi_cung) && (float)$this->gia_cuoi_cung === $new) {
            return $this;
        }

        $this->gia_cuoi_cung = $new;
        if (is_null($this->gia_mac_dinh) || (float)$this->gia_mac_dinh <= 0) {
            $this->gia_mac_dinh = $this->loaiPhong ? (float) ($this->loaiPhong->gia_mac_dinh ?? 0) : 0;
        }

        $this->save();

        return $this;
    }


    public function getTotalBedPrice(bool $reloadRelations = true): float
    {
        if ($reloadRelations) $this->loadMissing('bedTypes');

        $sum = 0.0;
        $beds = $this->relationLoaded('bedTypes') ? $this->bedTypes : $this->bedTypes()->get();
        foreach ($beds as $bt) {
            $qty = (int) ($bt->pivot->quantity ?? 0);
            if ($qty <= 0) continue;
            $pricePer = $bt->pivot->price !== null ? (float) $bt->pivot->price : (float) ($bt->price ?? 0);
            $sum += $qty * $pricePer;
        }
        return (float) $sum;
    }

    public function getTotalAmenitiesPrice(bool $reloadRelations = true): float
    {
        if ($reloadRelations) $this->loadMissing(['loaiPhong.tienNghis', 'tienNghis']);

        $typeAmenityIds = $this->loaiPhong ? $this->loaiPhong->tienNghis->pluck('id')->toArray() : [];
        $roomAmenityIds = $this->tienNghis ? $this->tienNghis->pluck('id')->toArray() : [];
        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        if (empty($allAmenityIds)) return 0.0;

        return (float) TienNghi::whereIn('id', $allAmenityIds)->sum('gia');
    }

    public function scopeWithPositivePrice($query)
    {
        return $query->where(function ($q) {
            $q->where('gia_cuoi_cung', '>', 0)
                ->orWhere('gia_mac_dinh', '>', 0);
        });
    }

    public function firstImagePath()
    {
        $img = $this->images->first();
        return $img ? $img->image_path : null;
    }

    public function firstImageUrl()
    {
        $path = $this->firstImagePath();
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }

        return asset('template/stackbros/assets/images/category/hotel/01.jpg');
    }
}
