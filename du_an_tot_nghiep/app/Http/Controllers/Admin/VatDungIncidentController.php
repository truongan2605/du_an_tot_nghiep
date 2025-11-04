<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VatDungIncident;
use App\Models\PhongVatDungInstance;
use App\Models\PhongVatDungConsumption;
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
            'mark_instance_status' => ['nullable', Rule::in(['damaged','missing','lost'])],
            'create_consumption' => 'nullable|boolean',
            'consumption_quantity' => 'nullable|integer|min:1',
        ]);

        $data['reported_by'] = Auth::id();
        $createConsumption = (bool) ($data['create_consumption'] ?? false);
        $consumptionQty = isset($data['consumption_quantity']) ? (int)$data['consumption_quantity'] : 1;

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

            $incident = VatDungIncident::create([
                'phong_vat_dung_instance_id' => $data['phong_vat_dung_instance_id'] ?? null,
                'phong_id' => $data['phong_id'] ?? null,
                'dat_phong_id' => $data['dat_phong_id'] ?? null,
                'vat_dung_id' => (int)$data['vat_dung_id'],
                'type' => $data['type'] ?? null,
                'description' => $data['description'] ?? null,
                'fee' => isset($data['fee']) ? (float)$data['fee'] : null,
                'reported_by' => $data['reported_by'],
            ]);

            if ($instance && !empty($data['mark_instance_status'])) {
                // normalize mapping mark_instance_status -> instance status
                $map = [
                    'damaged' => PhongVatDungInstance::STATUS_DAMAGED,
                    'missing' => PhongVatDungInstance::STATUS_MISSING,
                    'lost' => PhongVatDungInstance::STATUS_LOST,
                ];
                $instance->status = $map[$data['mark_instance_status']] ?? $instance->status;
                $instance->save();
            }

            if ($createConsumption) {
                $vat = VatDung::find($incident->vat_dung_id);
                $unitPrice = $vat ? ($vat->gia ?? 0) : 0;

                PhongVatDungConsumption::create([
                    'dat_phong_id' => $data['dat_phong_id'] ?? null,
                    'phong_id' => $data['phong_id'] ?? ($instance->phong_id ?? null),
                    'vat_dung_id' => $incident->vat_dung_id,
                    'quantity' => $consumptionQty,
                    'unit_price' => $unitPrice,
                    'note' => 'Auto-created from incident id=' . $incident->id,
                    'created_by' => Auth::id(),
                    'consumed_at' => now(),
                ]);

                // update pivot counters
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
                            'so_luong' => max(0, ((int)$pivot->so_luong) - $consumptionQty),
                            'da_tieu_thu' => ((int)$pivot->da_tieu_thu) + $consumptionQty,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $data['phong_id'] ?? ($instance->phong_id ?? null),
                        'vat_dung_id' => $incident->vat_dung_id,
                        'so_luong' => 0,
                        'da_tieu_thu' => $consumptionQty,
                        'gia_override' => null,
                        'tracked_instances' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Ghi nhận sự cố thành công');
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
