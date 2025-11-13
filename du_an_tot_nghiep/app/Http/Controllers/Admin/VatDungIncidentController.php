<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use Illuminate\Http\Request;
use App\Models\VatDungIncident;
use App\Models\PhongVatDungInstance;
use App\Models\VatDung;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VatDungIncidentController extends Controller
{
    public function createInstance(Request $request, $phongId)
    {
        $data = $request->validate([
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'status' => ['nullable', Rule::in(PhongVatDungInstance::allowedStatuses())],
        ]);

        $data['phong_id'] = $phongId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? PhongVatDungInstance::STATUS_PRESENT;

        $instance = PhongVatDungInstance::create([
            'phong_id' => $data['phong_id'],
            'vat_dung_id' => (int)$data['vat_dung_id'],
            'serial' => $data['serial'] ?? null,
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        return back()->with('success', 'Tạo bản vật dụng thành công');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phong_vat_dung_instance_id' => 'nullable|exists:phong_vat_dung_instances,id',
            'phong_id' => 'nullable|exists:phong,id',
            'dat_phong_id' => 'nullable|exists:dat_phong,id',
            'vat_dung_id' => 'nullable|exists:vat_dungs,id',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
            'mark_instance_status' => ['nullable', Rule::in(['damaged', 'missing', 'lost'])],
            'consumption_quantity' => 'nullable|integer|min:1',
        ]);

        $data['reported_by'] = Auth::id();
        $consumptionQty = isset($data['consumption_quantity']) ? (int)$data['consumption_quantity'] : 1;

        $map = [
            'damaged' => 'damage',
            'missing' => 'loss',
            'lost' => 'loss',
        ];

        $providedType = isset($data['type']) ? trim($data['type']) : null;
        if ($providedType) {
            $allowed = ['damage', 'loss', 'other'];
            if (!in_array($providedType, $allowed, true)) {
                $lower = strtolower($providedType);
                $data['type'] = $map[$lower] ?? 'other';
            }
        } else {
            if (!empty($data['mark_instance_status']) && isset($map[$data['mark_instance_status']])) {
                $data['type'] = $map[$data['mark_instance_status']];
            } else {
                $data['type'] = 'other';
            }
        }

        DB::beginTransaction();
        try {
            $instance = null;
            if (!empty($data['phong_vat_dung_instance_id'])) {
                $instance = PhongVatDungInstance::find($data['phong_vat_dung_instance_id']);
                if (!$instance) throw new \Exception('Instance không tồn tại');
                if (!empty($data['vat_dung_id']) && (int)$data['vat_dung_id'] !== (int)$instance->vat_dung_id) {
                    throw new \Exception('vat_dung_id không khớp với instance được cung cấp.');
                }
                $data['vat_dung_id'] = (int)$instance->vat_dung_id;
                if (empty($data['phong_id'])) $data['phong_id'] = $instance->phong_id;
            }

            if (empty($data['vat_dung_id'])) throw new \Exception('vat_dung_id là bắt buộc nếu không có phong_vat_dung_instance_id.');

            $datPhongId = $data['dat_phong_id'] ?? null;
            if (empty($datPhongId) && !empty($data['phong_id'])) {
                $phongModel = \App\Models\Phong::find($data['phong_id']);
                if ($phongModel) {
                    $active = $phongModel->activeDatPhong();
                    $datPhongId = $active ? $active->id : null;
                }
            }

            $incident = VatDungIncident::create([
                'phong_vat_dung_instance_id' => $data['phong_vat_dung_instance_id'] ?? null,
                'phong_id' => $data['phong_id'] ?? null,
                'dat_phong_id' => $datPhongId ?? null,
                'vat_dung_id' => (int)$data['vat_dung_id'],
                'type' => $data['type'] ?? 'other',
                'description' => $data['description'] ?? null,
                'fee' => isset($data['fee']) ? (float)$data['fee'] : null,
                'reported_by' => $data['reported_by'],
            ]);

            if ($instance && !empty($data['mark_instance_status'])) {
                $mapInst = [
                    'damaged' => PhongVatDungInstance::STATUS_DAMAGED,
                    'missing' => PhongVatDungInstance::STATUS_MISSING,
                    'lost' => PhongVatDungInstance::STATUS_LOST,
                ];
                $instance->status = $mapInst[$data['mark_instance_status']] ?? $instance->status;
                $instance->save();
            }

            if (!empty($datPhongId)) {
                $vat = VatDung::find($incident->vat_dung_id);
                $unitPrice = $incident->fee ?? ($vat->gia ?? 0);
                $qty = max(1, $consumptionQty);
                $amount = round($unitPrice * $qty, 2);

                $hoaDon = HoaDon::where('dat_phong_id', $datPhongId)
                    ->where('trang_thai', '!=', 'da_thanh_toan')
                    ->orderByDesc('id')
                    ->first();

                if (!$hoaDon) {
                    $hoaDon = HoaDon::create([
                        'dat_phong_id' => $datPhongId,
                        'so_hoa_don' => 'HD' . time(),
                        'tong_thuc_thu' => 0,
                        'don_vi' => 'VND',
                        'trang_thai' => 'tao',
                    ]);
                }

                $item = HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'incident',
                    'ref_id' => $incident->id,
                    'vat_dung_id' => $incident->vat_dung_id,
                    'name' => $vat->ten ?? ('Charge #' . $incident->id),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'note' => $incident->description ?? null,
                ]);

                $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $amount;
                $hoaDon->save();

                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $data['phong_id'] ?? ($instance->phong_id ?? null))
                    ->where('vat_dung_id', $incident->vat_dung_id)
                    ->lockForUpdate()
                    ->first();

                if ($pivot) {
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $data['phong_id'] ?? ($instance->phong_id ?? null))
                        ->where('vat_dung_id', $incident->vat_dung_id)
                        ->update([
                            'so_luong' => max(0, ((int)$pivot->so_luong) - $qty),
                            'da_tieu_thu' => ((int)$pivot->da_tieu_thu) + $qty,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $data['phong_id'] ?? ($instance->phong_id ?? null),
                        'vat_dung_id' => $incident->vat_dung_id,
                        'so_luong' => 0,
                        'da_tieu_thu' => $qty,
                        'gia_override' => null,
                        'tracked_instances' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // mark incident as billed (optional): you may want to set billed_at on incident
                $incident->billed_at = now();
                $incident->save();
            } else {
                // no booking found: we do not create invoice — but since buttons should be disabled,
                // typically this branch won't run. Keep the incident record only.
            }

            DB::commit();
            return back()->with('success', 'Ghi nhận sự cố thành công' . (!empty($datPhongId) ? ' và đã tính vào hoá đơn.' : '.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Không thể ghi nhận sự cố: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, VatDungIncident $incident)
    {
        $data = $request->validate([
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($incident, $data) {
            $incident->update($data);
        });

        return back()->with('success', 'Cập nhật sự cố thành công');
    }

    public function destroy(VatDungIncident $incident)
    {
        if ($incident->billed_at) {
            return back()->withErrors(['error' => 'Sự cố đã tính hoá đơn, không thể xóa.']);
        }

        DB::transaction(function () use ($incident) {
            $incident->delete();
        });

        return back()->with('success', 'Xóa sự cố thành công');
    }
}
