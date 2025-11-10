<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\PhongVatDungInstance;
use App\Models\VatDung;
use App\Models\PhongVatDungConsumption;
use App\Models\VatDungIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PhongVatDungInstanceController extends Controller
{
    public function index(Request $request, Phong $phong)
    {
        $query = PhongVatDungInstance::with('vatDung')->where('phong_id', $phong->id);
        $query->whereHas('vatDung', function ($q) {
            $q->where('loai', \App\Models\VatDung::LOAI_DO_DUNG);
        });

        $instances = $query->orderBy('created_at', 'desc')->get();

        return view('admin.phong.vatdung_instances.index', compact('phong', 'instances'));
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
            'create_consumption' => 'nullable|boolean',
            'consumption_quantity' => 'nullable|integer|min:1',
        ]);

        $data['reported_by'] = Auth::id();
        $createConsumption = (bool) ($data['create_consumption'] ?? false);
        $consumptionQty = isset($data['consumption_quantity']) ? (int)$data['consumption_quantity'] : 1;

        // normalize type to enum values in DB (damage|loss|other)
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
                if (isset($map[$lower])) {
                    $data['type'] = $map[$lower];
                } else {
                    $data['type'] = 'other';
                }
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

            // if dat_phong_id not provided, try to detect active booking from phong
            $datPhongId = $data['dat_phong_id'] ?? null;
            if (empty($datPhongId) && !empty($data['phong_id'])) {
                $phongModel = \App\Models\Phong::find($data['phong_id']);
                if ($phongModel) {
                    $active = $phongModel->activeDatPhong();
                    $datPhongId = $active ? $active->id : null;
                }
            }

            // create incident record
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

            if ($createConsumption) {
                if (empty($datPhongId)) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['error' => 'Không tìm thấy booking để tính tiền. Vui lòng mở booking hoặc bỏ chọn "Tự động tạo mục tính tiền".']);
                }

                // ensure vatDung model for naming / unit price
                $vat = VatDung::find($incident->vat_dung_id);
                $unitPrice = $incident->fee ?? ($vat->gia ?? 0);
                $qty = max(1, $consumptionQty);
                $amount = round($unitPrice * $qty, 2);

                // get or create HoaDon for this dat_phong (not paid)
                $hoaDon = \App\Models\HoaDon::where('dat_phong_id', $datPhongId)
                    ->where('trang_thai', '!=', 'da_thanh_toan')
                    ->orderByDesc('id')
                    ->first();

                if (! $hoaDon) {
                    $hoaDon = \App\Models\HoaDon::create([
                        'dat_phong_id' => $datPhongId,
                        'so_hoa_don' => 'HD' . time(),
                        'tong_thuc_thu' => 0,
                        'don_vi' => 'VND',
                        'trang_thai' => 'tao',
                    ]);
                }

                // tạo item trên hoá đơn (loại 'incident')
                $item = \App\Models\HoaDonItem::create([
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

                // cập nhật tổng hoá đơn
                $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $amount;
                $hoaDon->save();

                // Update pivot counters: giảm so_luong, tăng da_tieu_thu (lock)
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
            } // end createConsumption

            DB::commit();
            return back()->with('success', 'Ghi nhận sự cố thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Không thể ghi nhận sự cố: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, PhongVatDungInstance $instance)
    {
        $data = $request->validate([
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|in:present,damaged,missing,lost,archived',
        ]);

        $instance->fill($data);
        $instance->save();

        return back()->with('success', 'Cập nhật bản thể thành công');
    }

    public function updateStatus(Request $request, PhongVatDungInstance $instance)
    {
        $data = $request->validate([
            'status' => 'required|in:present,damaged,missing,lost,archived',
            'create_consumption' => 'nullable|boolean',
            'incident_fee' => 'nullable|numeric|min:0',
            'incident_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($instance, $data) {
            $old = $instance->status;
            $instance->status = $data['status'];
            $instance->save();

            if (in_array($data['status'], ['damaged', 'missing', 'lost'])) {
                $mapType = [
                    'damaged' => 'damage',
                    'missing' => 'loss',
                    'lost' => 'loss',
                ];
                $incidentType = $mapType[$data['status']] ?? 'other';

                \App\Models\VatDungIncident::create([
                    'phong_vat_dung_instance_id' => $instance->id,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'type' => $incidentType,
                    'description' => $data['incident_note'] ?? ('Status changed to ' . $data['status']),
                    'fee' => isset($data['incident_fee']) ? (float) $data['incident_fee'] : (optional($instance->vatDung)->gia ?? 0),
                    'reported_by' => Auth::id(),
                ]);
            }

            if (!empty($data['create_consumption'])) {
                PhongVatDungConsumption::create([
                    'dat_phong_id' => null,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'quantity' => (int) ($instance->quantity ?? 1),
                    'unit_price' => optional($instance->vatDung)->gia ?? 0,
                    'note' => 'Auto-charge due to instance status ' . $data['status'] . ' (instance id=' . $instance->id . ')',
                    'created_by' => Auth::id(),
                    'consumed_at' => now(),
                ]);

                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $instance->phong_id)
                    ->where('vat_dung_id', $instance->vat_dung_id)
                    ->lockForUpdate()
                    ->first();

                if ($pivot) {
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $instance->phong_id)
                        ->where('vat_dung_id', $instance->vat_dung_id)
                        ->update([
                            'so_luong' => max(0, ((int)$pivot->so_luong) - (int)$instance->quantity),
                            'da_tieu_thu' => ((int)$pivot->da_tieu_thu) + (int)$instance->quantity,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $instance->phong_id,
                        'vat_dung_id' => $instance->vat_dung_id,
                        'so_luong' => 0,
                        'da_tieu_thu' => (int)$instance->quantity,
                        'gia_override' => null,
                        'tracked_instances' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Cập nhật trạng thái thành công');
    }


    public function destroy(PhongVatDungInstance $instance)
    {
        DB::transaction(function () use ($instance) {
            DB::table('phong_vat_dung')
                ->where('phong_id', $instance->phong_id)
                ->where('vat_dung_id', $instance->vat_dung_id)
                ->decrement('so_luong', (int) ($instance->quantity ?? 1));

            $instance->delete();
        });

        return back()->with('success', 'Xóa bản thể thành công');
    }
}
