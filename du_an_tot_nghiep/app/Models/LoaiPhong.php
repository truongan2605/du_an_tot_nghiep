<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoaiPhong extends Model
{
    use HasFactory;
    use Auditable;

    protected $table = 'loai_phong';

    protected $fillable = [
        'ma',
        'ten',
        'mo_ta',
        'suc_chua',
        'so_giuong',
        'gia_mac_dinh',
        'so_luong_thuc_te',
        'active',
    ];

    protected $casts = [
        'suc_chua' => 'integer',
        'so_giuong' => 'integer',
        'gia_mac_dinh' => 'decimal:2',
        'so_luong_thuc_te' => 'integer',
        'active' => 'boolean',
    ];

    public function phongs()
    {
        return $this->hasMany(Phong::class, 'loai_phong_id');
    }

    public function tienNghis()
    {
        return $this->belongsToMany(TienNghi::class, 'loai_phong_tien_nghi')
            ->withPivot('price') //Them
            ->where('tien_nghi.active', true);
    }

    public function vatDungs()
    {
        return $this->belongsToMany(VatDung::class, 'loai_phong_vat_dung', 'loai_phong_id', 'vat_dung_id')
            ->where('vat_dungs.active', true);
    }


    public function bedTypes()
    {
        return $this->belongsToMany(BedType::class, 'loai_phong_bed_type')
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }

    public static function refreshSoLuongThucTe(int $loaiPhongId): int
    {
        $count = \App\Models\Phong::where('loai_phong_id', $loaiPhongId)->count();

        static::where('id', $loaiPhongId)->update(['so_luong_thuc_te' => $count]);

        return $count;
    }

    public static function refreshAllSoLuongThucTe(): void
    {
        $counts = \App\Models\Phong::select('loai_phong_id', DB::raw('count(*) as cnt'))
            ->groupBy('loai_phong_id')
            ->pluck('cnt', 'loai_phong_id')
            ->toArray();

        $allIds = static::pluck('id')->toArray();

        foreach ($allIds as $id) {
            $val = isset($counts[$id]) ? (int)$counts[$id] : 0;
            static::where('id', $id)->update(['so_luong_thuc_te' => $val]);
        }
    }
}
