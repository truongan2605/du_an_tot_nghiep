<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phong;
use App\Models\VatDung;
use Illuminate\Support\Facades\DB;

class PhongVatDungController extends Controller
{
    public function sync(Request $request, Phong $phong)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.vat_dung_id' => 'required|exists:vat_dungs,id',
            'items.*.so_luong' => 'nullable|integer|min:0',
            'items.*.da_tieu_thu' => 'nullable|integer|min:0',
            'items.*.gia_override' => 'nullable|numeric|min:0',
            'items.*.tracked_instances' => 'nullable|boolean',
        ]);

        $attach = [];
        foreach ($data['items'] as $it) {
            $attach[$it['vat_dung_id']] = [
                'so_luong' => $it['so_luong'] ?? 0,
                'da_tieu_thu' => $it['da_tieu_thu'] ?? 0,
                'gia_override' => isset($it['gia_override']) ? (float)$it['gia_override'] : null,
                'tracked_instances' => isset($it['tracked_instances']) ? (bool)$it['tracked_instances'] : false,
            ];
        }

        DB::transaction(function () use ($phong, $attach) {
            $phong->vatDungs()->sync($attach);
        });

        return back()->with('success', 'Cập nhật vật dụng phòng thành công');
    }

    public function remove(Phong $phong, VatDung $vat_dung)
    {
        $phong->vatDungs()->detach($vat_dung->id);
        return back()->with('success', 'Đã gỡ vật dụng khỏi phòng');
    }
}
