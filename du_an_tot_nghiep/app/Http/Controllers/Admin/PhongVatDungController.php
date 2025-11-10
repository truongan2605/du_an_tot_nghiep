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
    protected function normalizeItemsArray($raw)
    {
        $items = [];
        foreach ($raw as $k => $v) {
            if (!is_array($v)) {
                continue;
            }

            if (isset($v['vat_dung_id'])) {
                // form used: items[][vat_dung_id] ... (indexed array)
                $vdId = (int) $v['vat_dung_id'];

                $qty = isset($v['so_luong']) ? (int)$v['so_luong']
                    : (isset($v['quantity']) ? (int)$v['quantity'] : 0);

                $items[$vdId] = [
                    'so_luong' => $qty,
                    'da_tieu_thu' => isset($v['da_tieu_thu']) ? (int)$v['da_tieu_thu'] : 0,
                    'gia_override' => isset($v['gia_override']) ? (float)$v['gia_override']
                        : (isset($v['unit_price']) ? (float)$v['unit_price'] : null),
                    'tracked_instances' => isset($v['tracked_instances']) ? (bool)$v['tracked_instances'] : false,
                ];
            } else {
                // keyed by vat_dung_id: items[<vat_dung_id>][quantity] ...
                $vdId = (int) $k;

                $qty = isset($v['so_luong']) ? (int)$v['so_luong']
                    : (isset($v['quantity']) ? (int)$v['quantity'] : 0);

                $items[$vdId] = [
                    'so_luong' => $qty,
                    'da_tieu_thu' => isset($v['da_tieu_thu']) ? (int)$v['da_tieu_thu'] : 0,
                    'gia_override' => isset($v['gia_override']) ? (float)$v['gia_override']
                        : (isset($v['unit_price']) ? (float)$v['unit_price'] : null),
                    'tracked_instances' => isset($v['tracked_instances']) ? (bool)$v['tracked_instances'] : false,
                ];
            }
        }
        return $items;
    }

    public function sync(Request $request, Phong $phong)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $raw = $request->input('items', []);
        $items = $this->normalizeItemsArray($raw);

        // Verify: do not allow editing do_dung at room level
        foreach ($items as $vdId => $vals) {
            $vd = VatDung::find($vdId);
            if ($vd && ($vd->loai ?? '') === VatDung::LOAI_DO_DUNG) {
                return back()->withErrors([
                    'error' => "Không thể chỉnh vật dụng loại 'đồ dùng' ở cấp phòng. Vui lòng quản lý vật dụng này ở phần Loại phòng."
                ]);
            }
        }

        DB::transaction(function () use ($phong, $items) {
            // prepare attach structure for sync
            $attach = [];
            $now = now();
            foreach ($items as $vdId => $vals) {
                $attach[$vdId] = [
                    'so_luong' => $vals['so_luong'] ?? 0,
                    'da_tieu_thu' => $vals['da_tieu_thu'] ?? 0,
                    'gia_override' => $vals['gia_override'] ?? null,
                    'tracked_instances' => $vals['tracked_instances'] ?? false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $phong->vatDungs()->sync($attach);

            // ensure instances or archive if not tracked
            foreach ($attach as $vatDungId => $pivotData) {
                $tracked = (bool)($pivotData['tracked_instances'] ?? false);
                $soLuong = (int)($pivotData['so_luong'] ?? 0);

                if ($tracked) {
                    \App\Models\PhongVatDung::ensureInstancesForPivotRow($phong->id, $vatDungId, $soLuong, Auth::id());
                } else {
                    DB::table('phong_vat_dung_instances')
                        ->where('phong_id', $phong->id)
                        ->where('vat_dung_id', $vatDungId)
                        ->where('status', \App\Models\PhongVatDungInstance::STATUS_PRESENT)
                        ->update(['status' => \App\Models\PhongVatDungInstance::STATUS_ARCHIVED, 'updated_at' => now()]);
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
            $showAllowed = $datPhong->canSetupConsumables();
        } else {
            $showAllowed = in_array($phong->trang_thai, ['da_dat', 'dang_o']);
        }

        $doAnList = VatDung::where('active', true)
            ->where('loai', VatDung::LOAI_DO_AN)
            ->orderBy('ten')
            ->get();

        $existingReservations = collect();
        if ($datPhong) {
            $existingReservations = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phong->id)
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->get()
                ->keyBy('vat_dung_id');
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
        $payload = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'items' => 'nullable|array',
            'note' => 'nullable|string|max:1000',
        ]);

        $datPhong = DatPhong::findOrFail($payload['dat_phong_id']);

        if (!$datPhong->canSetupConsumables()) {
            return back()->withErrors(['error' => 'Không thể setup đồ ăn cho booking có trạng thái hiện tại.']);
        }

        $raw = $request->input('items', []);
        $items = $this->normalizeItemsArray($raw);

        $positiveItems = array_filter($items, function ($v) {
            $qty = (int)($v['so_luong'] ?? $v['quantity'] ?? 0);
            return $qty > 0;
        });

        DB::transaction(function () use ($datPhong, $phong, $positiveItems, $request) {
            $existing = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phong->id)
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('vat_dung_id');

            foreach ($positiveItems as $vdId => $vals) {
                $qty = (int)($vals['so_luong'] ?? $vals['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $vat = VatDung::find($vdId);
                if (! $vat) continue;

                $pivot = DB::table('phong_vat_dung')->where('phong_id', $phong->id)->where('vat_dung_id', $vdId)->first();
                $unitPrice = $vals['gia_override'] ?? $vals['unit_price'] ?? (optional($pivot)->gia_override ?? $vat->gia ?? 0);

                if ($existing->has($vdId)) {
                    $row = $existing->get($vdId);
                    $row->quantity = $qty;
                    $row->unit_price = $unitPrice;
                    $row->note = $request->input('note', $row->note);
                    $row->save();
                } else {
                    PhongVatDungConsumption::create([
                        'dat_phong_id' => $datPhong->id,
                        'phong_id' => $phong->id,
                        'vat_dung_id' => $vdId,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'note' => $request->input('note', null),
                        'created_by' => Auth::id(),
                        'consumed_at' => null,
                    ]);
                }
            }

            $incomingIds = array_map('intval', array_keys($positiveItems));
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

    public function storeItem(Request $request, Phong $phong)
    {
        $data = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'items' => 'required|array',
        ]);

        $datPhong = DatPhong::findOrFail($data['dat_phong_id']);
        if (! $datPhong->canSetupConsumables()) {
            return response()->json(['error' => 'Booking không hợp lệ'], 422);
        }

        $raw = $request->input('items', []);
        $items = $this->normalizeItemsArray($raw);
        $positive = array_filter($items, fn($v) => (int)($v['so_luong'] ?? $v['quantity'] ?? 0) > 0);

        if (empty($positive)) {
            return response()->json(['error' => 'No positive item provided'], 422);
        }

        $vdId = (int) array_keys($positive)[0];
        $vals = $positive[$vdId];
        $qty = (int) ($vals['so_luong'] ?? $vals['quantity'] ?? 0);

        DB::transaction(function () use ($datPhong, $phong, $vdId, $qty, $vals) {
            $existing = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phong->id)
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->where('vat_dung_id', $vdId)
                ->lockForUpdate()
                ->first();

            $vat = VatDung::find($vdId);
            $pivot = DB::table('phong_vat_dung')->where('phong_id', $phong->id)->where('vat_dung_id', $vdId)->first();
            $unitPrice = $vals['gia_override'] ?? $vals['unit_price'] ?? ($pivot->gia_override ?? ($vat->gia ?? 0));

            if ($existing) {
                $existing->quantity = $qty;
                $existing->unit_price = $unitPrice;
                $existing->save();
            } else {
                PhongVatDungConsumption::create([
                    'dat_phong_id' => $datPhong->id,
                    'phong_id' => $phong->id,
                    'vat_dung_id' => $vdId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'created_by' => Auth::id(),
                    'consumed_at' => null,
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function updateItem(Request $request, Phong $phong, PhongVatDungConsumption $consumption)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0'
        ]);

        if ($consumption->billed_at) {
            return response()->json(['error' => 'Đã billed'], 422);
        }

        $consumption->quantity = (int)$data['quantity'];
        if (isset($data['unit_price'])) $consumption->unit_price = (float)$data['unit_price'];
        $consumption->save();

        return response()->json(['ok' => true]);
    }

    public function destroyItem(Request $request, Phong $phong, $vatDungOrConsumptionId)
    {
        $cons = PhongVatDungConsumption::find($vatDungOrConsumptionId);
        if ($cons) {
            if ($cons->billed_at) return response()->json(['error' => 'Billed, cannot delete'], 422);
            $cons->delete();
            return response()->json(['ok' => true]);
        }

        $datPhongId = $request->input('dat_phong_id', null);
        if (! $datPhongId) return response()->json(['error' => 'dat_phong_id required'], 422);

        PhongVatDungConsumption::where('dat_phong_id', $datPhongId)
            ->where('phong_id', $phong->id)
            ->where('vat_dung_id', (int)$vatDungOrConsumptionId)
            ->whereNull('consumed_at')
            ->whereNull('billed_at')
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function remove(Phong $phong, VatDung $vat_dung)
    {
        $phong->vatDungs()->detach($vat_dung->id);
        return back()->with('success', 'Đã gỡ vật dụng khỏi phòng');
    }
}
