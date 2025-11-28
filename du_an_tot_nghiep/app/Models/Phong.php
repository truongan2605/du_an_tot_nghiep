<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Phong extends Model
{
    use HasFactory;
    use Auditable;
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
        'don_dep',
        'last_checked_at',
        'spec_signature_hash',
    ];

    protected $casts = [
        'suc_chua' => 'integer',
        'so_giuong' => 'integer',
        'gia_mac_dinh' => 'decimal:2',
        'gia_cuoi_cung' => 'decimal:2',
        'last_checked_at' => 'datetime',
        'don_dep' => 'boolean',
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
        return $this->belongsToMany(TienNghi::class, 'phong_tien_nghi')
            ->where('tien_nghi.active', true);
    }


    public function vatDungs()
    {
        return $this->belongsToMany(\App\Models\VatDung::class, 'phong_vat_dung')
            ->using(\App\Models\PhongVatDung::class)
            ->withPivot(['so_luong', 'da_tieu_thu', 'gia_override', 'tracked_instances'])
            ->withTimestamps();
    }


    public function vatDungInstances()
    {
        return $this->hasMany(\App\Models\PhongVatDungInstance::class, 'phong_id');
    }

    public function vatDungIncidents()
    {
        return $this->hasMany(\App\Models\VatDungIncident::class, 'phong_id');
    }


    public function computeConsumableCharges(): float
    {
        $this->loadMissing(['vatDungs']);

        $total = 0.0;
        foreach ($this->vatDungs as $vd) {
            if (! $vd->isConsumable()) continue;
            $consumed = (int) ($vd->pivot->da_tieu_thu ?? 0);
            if ($consumed <= 0) continue;
            $unitPrice = $vd->pivot->gia_override !== null ? (float) $vd->pivot->gia_override : (float) ($vd->gia ?? 0);
            $total += $consumed * $unitPrice;
        }
        return (float) $total;
    }

    public function computeIncidentCharges(): float
    {
        // eager load incidents
        $this->loadMissing(['vatDungIncidents']);
        return (float) $this->vatDungIncidents->sum(function ($inc) {
            return (float) ($inc->fee ?? 0);
        });
    }

    public function activeDatPhong(): ?\App\Models\DatPhong
    {
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $dp = \App\Models\DatPhong::whereHas('datPhongItems', function ($q) {
                $q->where('phong_id', $this->id);
            })
                ->whereIn('trang_thai', ['da_dat', 'dang_su_dung', 'da_xac_nhan'])
                ->orderByDesc('id')
                ->first();

            if ($dp) return $dp;
        }

        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $row = DB::table('giu_phong')
                ->where('phong_id', $this->id)
                ->where('released', false)
                ->where('het_han_luc', '>', now())
                ->orderByDesc('id')
                ->first();

            if ($row && !empty($row->dat_phong_id)) {
                return \App\Models\DatPhong::find($row->dat_phong_id);
            }
        }

        return null;
    }

    public function computeVatDungCharges(): float
    {
        return $this->computeConsumableCharges() + $this->computeIncidentCharges();
    }


    public function bedTypes()
    {
        return $this->belongsToMany(BedType::class, 'phong_bed_type')
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }
    public function datPhongItems()
    {
        return $this->hasMany(DatPhongItem::class, 'phong_id');
    }
    public function images()
    {
        return $this->hasMany(PhongImage::class, 'phong_id')->orderBy('id', 'asc');
    }

    public function phongDaDats()
    {
        // đã cập nhật
        return $this->hasMany(DatPhong::class, 'phong_id');
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

        $base = (float) ($this->loaiPhong->gia_mac_dinh ?? $this->gia_mac_dinh ?? 0);

        $typeAmenityIds = $this->loaiPhong && $this->loaiPhong->relationLoaded('tienNghis')
            ? $this->loaiPhong->tienNghis->pluck('id')->toArray()
            : ($this->loaiPhong ? $this->loaiPhong->tienNghis()->pluck('id')->toArray() : []);

        $roomAmenityIds = $this->relationLoaded('tienNghis')
            ? $this->tienNghis->pluck('id')->toArray()
            : $this->tienNghis()->pluck('id')->toArray();

        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        $amenitiesSum = 0.0;
        if (!empty($allAmenityIds)) {
            $canSumFromCollections = $this->relationLoaded('tienNghis') && $this->loaiPhong && $this->loaiPhong->relationLoaded('tienNghis');
            if ($canSumFromCollections) {
                $merged = $this->tienNghis->merge($this->loaiPhong->tienNghis)->unique('id');
                $amenitiesSum = (float) $merged->sum(function ($a) {
                    return (float) ($a->gia ?? 0);
                });
            } else {
                $amenitiesSum = (float) TienNghi::whereIn('id', $allAmenityIds)->sum('gia');
            }
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

        if (!$forceOverwrite && !is_null($this->gia_cuoi_cung)) {
            $current = (float) $this->gia_cuoi_cung;
            if (abs($current - $new) < 0.01) {
                return $this;
            }
        }

        $this->gia_cuoi_cung = $new;
        if (is_null($this->gia_mac_dinh) || (float)$this->gia_mac_dinh <= 0) {
            $this->gia_mac_dinh = $this->loaiPhong ? (float) ($this->loaiPhong->gia_mac_dinh ?? 0) : 0;
        }

        $this->save();

        return $this;
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'wishlists', 'phong_id', 'user_id');
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

        $canSumFromCollections = $this->relationLoaded('tienNghis') && $this->loaiPhong && $this->loaiPhong->relationLoaded('tienNghis');
        if ($canSumFromCollections) {
            $merged = $this->tienNghis->merge($this->loaiPhong->tienNghis)->unique('id');
            return (float) $merged->sum(function ($a) {
                return (float) ($a->gia ?? 0);
            });
        }

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

    public function activeOverrides()
    {
        return $this->hasMany(\App\Models\PhongTienNghiOverride::class, 'phong_id')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function effectiveTienNghiIds(): array
    {
        $base = $this->relationLoaded('tienNghis') ? $this->tienNghis->pluck('id')->toArray()
            : $this->tienNghis()->pluck('id')->toArray();

        $overrideIds = $this->relationLoaded('activeOverrides')
            ? $this->activeOverrides->pluck('tien_nghi_id')->toArray()
            : \App\Models\PhongTienNghiOverride::where('phong_id', $this->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('tien_nghi_id')->toArray();

        $merged = array_values(array_unique(array_merge($base, $overrideIds)));
        sort($merged, SORT_NUMERIC);
        return $merged;
    }

    public function effectiveBedSpec(): array
    {
        $beds = $this->relationLoaded('bedTypes') ? $this->bedTypes : $this->bedTypes()->get();
        $arr = [];
        foreach ($beds as $bt) {
            $arr[] = [
                'id' => (int) $bt->id,
                'quantity' => (int) ($bt->pivot->quantity ?? 0),
                'price' => $bt->pivot->price !== null ? (float)$bt->pivot->price : ((float)($bt->price ?? 0))
            ];
        }
        usort($arr, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        return $arr;
    }

    public function hasBookings(): bool
    {
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $exists = DB::table('dat_phong_item')
                ->where('phong_id', $this->id)
                ->exists();
            if ($exists) return true;
        }

        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $exists = DB::table('giu_phong')
                ->where('phong_id', $this->id)
                ->where('released', false)
                ->exists();
            if ($exists) return true;
        }

        return false;
    }

    public function specSignatureArray(): array
    {
        $sig = [
            'loai_phong_id' => (int) $this->loai_phong_id,
            'tien_nghi' => $this->effectiveTienNghiIds(),
            'beds' => $this->effectiveBedSpec(),
        ];

        ksort($sig);
        return $sig;
    }

    public function specSignatureHash(): string
    {
        $sig = $this->specSignatureArray();
        return md5(json_encode($sig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    public function danhGias()
    {
        return $this->hasManyThrough(DanhGia::class, DatPhong::class, 'phong_id', 'dat_phong_id', 'id', 'id');
    }
    protected static function booted()
    {
        static::saving(function ($phong) {
            try {
                $phong->spec_signature_hash = $phong->specSignatureHash();
            } catch (\Throwable $e) {
                Log::warning('Could not compute spec_signature_hash for Phong id=' . ($phong->id ?? 'new') . ': ' . $e->getMessage());
            }
        });
    }
}
