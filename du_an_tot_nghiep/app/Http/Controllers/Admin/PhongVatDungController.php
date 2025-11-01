<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use Illuminate\Http\Request;
use App\Models\Phong;
use App\Models\PhongVatDungConsumption;
use App\Models\VatDung;
use Illuminate\Support\Facades\Auth;
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

        // verify types: không cho chỉnh đồ dùng (do_dung) ở cấp phòng
        foreach ($data['items'] as $it) {
            $vd = VatDung::find($it['vat_dung_id']);
            if (!$vd) continue;
            if ($vd->loai === VatDung::LOAI_DO_DUNG) {
                return back()->withErrors([
                    'error' => "Không thể chỉnh vật dụng loại 'đồ dùng' ở cấp phòng. Vui lòng quản lý vật dụng này ở phần Loại phòng."
                ]);
            }
        }

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

            foreach ($attach as $vatDungId => $pivotData) {
                $tracked = (bool)($pivotData['tracked_instances'] ?? false);
                $soLuong = (int)($pivotData['so_luong'] ?? 0);

                if ($tracked) {
                    \App\Models\PhongVatDung::ensureInstancesForPivotRow($phong->id, $vatDungId, $soLuong, Auth::id());
                } else {
                    DB::table('phong_vat_dung_instances')
                        ->where('phong_id', $phong->id)
                        ->where('vat_dung_id', $vatDungId)
                        ->where('status', 'ok')
                        ->update(['status' => 'archived', 'updated_at' => now()]);
                }
            }
        });

        return back()->with('success', 'Cập nhật vật dụng phòng thành công');
    }


    public function showFoodSetup(Request $request, Phong $phong)
    {
        $datPhongId = $request->query('dat_phong_id', null);
        $datPhong = $datPhongId ? DatPhong::find($datPhongId) : null;

        $showAllowed = true;
        if ($datPhong) {
            $showAllowed = in_array($datPhong->trang_thai, ['da_dat', 'dang_su_dung', 'da_xac_nhan']);
        } else {
            $showAllowed = in_array($phong->trang_thai, ['da_dat', 'dang_o']);
        }

        // Lấy danh sách đồ ăn
        $doAnList = VatDung::where('active', true)
            ->where('loai', VatDung::LOAI_DO_AN)
            ->orderBy('ten')
            ->get();

        // Lấy các reservation hiện có (reserved = consumed_at IS NULL, not billed)
        $existingReservations = collect();
        if ($datPhong) {
            $existingReservations = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phong->id)
                ->whereNull('consumed_at')    // reserved but not started yet
                ->whereNull('billed_at')     // not billed yet
                ->get()
                ->keyBy('vat_dung_id'); // keyed by vat_dung_id for easy lookup in view
        }

        return view('admin.phong.food-setup', [
            'phong' => $phong,
            'datPhong' => $datPhong,
            'doAnList' => $doAnList,
            'showAllowed' => $showAllowed,
            'existingReservations' => $existingReservations,
        ]);
    }

    public function reserveFood(Request $request, Phong $phong)
    {
        // dat_phong_id required (we consider reservations attached to a booking)
        $data = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'items' => 'nullable|array',
            'items.*.vat_dung_id' => 'required|exists:vat_dungs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        $datPhong = DatPhong::find($data['dat_phong_id']);
        if (!$datPhong) {
            return back()->withErrors(['error' => 'Booking không tồn tại.']);
        }

        // Only allow setup for bookings in allowed states
        if (! in_array($datPhong->trang_thai, ['da_dat', 'da_xac_nhan', 'dang_su_dung'])) {
            return back()->withErrors(['error' => 'Không thể setup đồ ăn cho booking có trạng thái hiện tại.']);
        }

        // Safety: ensure phong status align as well (optional)
        if (! in_array($phong->trang_thai, ['da_dat', 'dang_o'])) {
            return back()->withErrors(['error' => 'Không thể setup đồ ăn cho phòng có trạng thái hiện tại.']);
        }

        // incoming items keyed by vat_dung_id (because view uses items[vdId][])
        $incoming = $request->input('items', []); // may be null/empty if all unchecked
        $incomingIds = array_keys($incoming ?: []);

        DB::transaction(function () use ($datPhong, $phong, $incoming, $incomingIds, $request) {
            // load existing reservations (reserved but not consumed/billed)
            $existing = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phong->id)
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->get()
                ->keyBy('vat_dung_id');

            // Upsert incoming items
            foreach ($incoming as $vdId => $vals) {
                // skip if malformed
                $vatDungId = isset($vals['vat_dung_id']) ? (int)$vals['vat_dung_id'] : (int)$vdId;
                $qty = (int)($vals['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $unitPrice = isset($vals['unit_price']) ? (float)$vals['unit_price'] : null;
                $note = $request->input('note', null);

                if ($existing->has($vatDungId)) {
                    // update existing reservation
                    $row = $existing->get($vatDungId);
                    $row->quantity = $qty;
                    if ($unitPrice !== null) $row->unit_price = $unitPrice;
                    if ($note !== null) $row->note = $note;
                    $row->save();
                } else {
                    // create new reservation (consumed_at = NULL)
                    PhongVatDungConsumption::create([
                        'dat_phong_id' => $datPhong->id,
                        'phong_id' => $phong->id,
                        'vat_dung_id' => $vatDungId,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'note' => $note,
                        'created_by' => Auth::id(),
                        'consumed_at' => null,
                    ]);
                }
            }

            // Delete any existing reservations that are no longer present in incoming items
            $toDelete = $existing->keys()->diff($incomingIds)->toArray();
            if (!empty($toDelete)) {
                PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                    ->where('phong_id', $phong->id)
                    ->whereNull('consumed_at')
                    ->whereNull('billed_at')
                    ->whereIn('vat_dung_id', $toDelete)
                    ->delete();
            }
        });

        return redirect()->route('admin.phong.food-setup', [
            'phong' => $phong->id,
            'dat_phong_id' => $datPhong->id,
        ])->with('success', 'Cập nhật setup đồ ăn thành công.');
    }

    public function remove(Phong $phong, VatDung $vat_dung)
    {
        $phong->vatDungs()->detach($vat_dung->id);
        return back()->with('success', 'Đã gỡ vật dụng khỏi phòng');
    }
}
