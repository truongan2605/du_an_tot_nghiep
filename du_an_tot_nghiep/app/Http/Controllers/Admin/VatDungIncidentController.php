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
            'status' => ['nullable', Rule::in(['ok','lost','damaged','used','archived'])],
        ]);

        $data['phong_id'] = $phongId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'ok';

        // Tạo instance (model PhongVatDungInstance phải có fillable tương ứng)
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
            'mark_instance_status' => ['nullable', Rule::in(['ok','lost','damaged','used','archived'])],
            'create_consumption' => 'nullable|boolean',
            'consumption_quantity' => 'nullable|integer|min:1',
        ]);

        // normalize
        $data['reported_by'] = Auth::id();
        $createConsumption = (bool) ($data['create_consumption'] ?? false);
        $consumptionQty = isset($data['consumption_quantity']) ? (int)$data['consumption_quantity'] : 1;

        DB::beginTransaction();
        try {
            // If an instance id provided, load it and ensure consistency
            $instance = null;
            if (!empty($data['phong_vat_dung_instance_id'])) {
                $instance = PhongVatDungInstance::find($data['phong_vat_dung_instance_id']);
                if (!$instance) {
                    throw new \Exception('Instance không tồn tại');
                }
                // If vat_dung_id provided, ensure match
                if (!empty($data['vat_dung_id']) && (int)$data['vat_dung_id'] !== (int)$instance->vat_dung_id) {
                    throw new \Exception('vat_dung_id không khớp với instance được cung cấp.');
                }
                // fill vat_dung_id from instance if not provided
                $data['vat_dung_id'] = (int)$instance->vat_dung_id;
                // also fill phong_id if missing
                if (empty($data['phong_id'])) {
                    $data['phong_id'] = $instance->phong_id;
                }
            }

            // If only vat_dung_id provided but phong_vat_dung_instance_id not, that's okay (incident about item type)
            if (empty($data['vat_dung_id'])) {
                throw new \Exception('vat_dung_id là bắt buộc nếu không có phong_vat_dung_instance_id.');
            }

            // Create incident
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

            // If admin wants to mark instance status (e.g. lost/damaged), do it
            if ($instance && !empty($data['mark_instance_status'])) {
                $instance->status = $data['mark_instance_status'];
                $instance->save();
            }

            // Optionally create consumption (charge), e.g. when lost/damaged and you want to charge immediately
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
                    'consumed_at' => now(), // mark as already consumed
                ]);
            }

            DB::commit();
            return back()->with('success', 'Ghi nhận sự cố thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Không thể ghi nhận sự cố: ' . $e->getMessage()]);
        }
    }

    /**
     * Cập nhật một incident (chỉnh type/description/fee).
     * Không cho phép đặt billed_at ở đây (để tránh thao tác vô tình) — billing nên có flow riêng.
     */
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
