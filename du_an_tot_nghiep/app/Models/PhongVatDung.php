<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PhongVatDung extends Pivot
{   
    use Auditable
    ;
    protected $table = 'phong_vat_dung';
    public $incrementing = false;
    protected $fillable = [
        'phong_id',
        'vat_dung_id',
        'so_luong',
        'da_tieu_thu',
        'gia_override',
        'tracked_instances',
    ];
    protected $casts = [
        'so_luong' => 'integer',
        'da_tieu_thu' => 'integer',
        'gia_override' => 'decimal:2',
        'tracked_instances' => 'boolean',
    ];


    public static function ensureInstancesForPivotRow($phongId, $vatDungId, int $targetQty, $createdBy = null)
    {
        $existsCount = DB::table('phong_vat_dung_instances')
            ->where('phong_id', $phongId)
            ->where('vat_dung_id', $vatDungId)
            ->where('status', 'present')
            ->count();

        if ($existsCount < $targetQty) {
            $create = $targetQty - $existsCount;
            $now = now();
            $rows = [];
            for ($i = 0; $i < $create; $i++) {
                $rows[] = [
                    'phong_id' => $phongId,
                    'vat_dung_id' => $vatDungId,
                    'serial' => null,
                    'status' => 'present',
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($rows)) DB::table('phong_vat_dung_instances')->insert($rows);
        } elseif ($existsCount > $targetQty) {
            // nếu tồn nhiều hơn target, ta không tự động xóa instance (vì có thể đã used/linked).
            // Option: archive extra ones (nếu bạn muốn)
        }
    }
}
