<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\VatDungIncident;
use App\Models\PhongVatDungInstance;
use App\Models\VatDung;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VatDungIncidentController extends Controller
{
    public function store(Request $request, \App\Models\DatPhong $booking)
    {
        $data = $request->validate([
            'phong_vat_dung_instance_id' => 'nullable|exists:phong_vat_dung_instances,id',
            'phong_id' => 'nullable|exists:phong,id',
            'vat_dung_id' => 'nullable|exists:vat_dungs,id',
            'mark_instance_status' => ['nullable', Rule::in(['damaged', 'missing'])],
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
            'consumption_quantity' => 'nullable|integer|min:1',
        ]);

        // normalize
        $data['reported_by'] = Auth::id();
        $datPhongId = $booking->id;
        $qty = max(1, (int)($data['consumption_quantity'] ?? 1));

        DB::beginTransaction();
        try {
            $instance = null;

            if (!empty($data['phong_vat_dung_instance_id'])) {
                $instance = PhongVatDungInstance::find($data['phong_vat_dung_instance_id']);
                if (!$instance) throw new \Exception('Instance không tồn tại');

                if ($instance->phong_id && $data['phong_id'] && (int)$instance->phong_id !== (int)$data['phong_id']) {
                    throw new \Exception('Instance không thuộc phòng được gửi.');
                }

                $belongs = DB::table('dat_phong_item')
                    ->where('dat_phong_id', $datPhongId)
                    ->where('phong_id', $instance->phong_id)
                    ->exists();

                if (!$belongs) {
                    if (Schema::hasTable('giu_phong')) {
                        $belongs = DB::table('giu_phong')->where('dat_phong_id', $datPhongId)->where('phong_id', $instance->phong_id)->exists();
                    }
                }

                if (!$belongs) throw new \Exception('Instance này không thuộc booking hiện tại.');
            }

            if (empty($data['vat_dung_id']) && $instance) {
                $data['vat_dung_id'] = $instance->vat_dung_id;
            }

            if (empty($data['vat_dung_id'])) throw new \Exception('vat_dung_id bắt buộc.');

            // If marking instance status (damaged/missing) - only allowed if instance currently present
            if (!empty($data['mark_instance_status']) && $instance) {
                if ($instance->status !== PhongVatDungInstance::STATUS_PRESENT) {
                    throw new \Exception('Chỉ được ghi nhận sự cố khi bản thể đang ở trạng thái "Nguyên vẹn".');
                }

                // ensure no existing incident for this instance + booking
                $exists = VatDungIncident::where('phong_vat_dung_instance_id', $instance->id)
                    ->where('dat_phong_id', $datPhongId)
                    ->exists();

                if ($exists) throw new \Exception('Đã có sự cố cho bản thể này trong booking hiện tại.');
            }

            // create incident
            $mapType = ['damaged' => 'damage', 'missing' => 'loss'];
            $type = !empty($data['mark_instance_status']) ? ($mapType[$data['mark_instance_status']] ?? 'other') : 'other';

            $incident = VatDungIncident::create([
                'phong_vat_dung_instance_id' => $instance->id ?? null,
                'phong_id' => $instance->phong_id ?? ($data['phong_id'] ?? null),
                'dat_phong_id' => $datPhongId,
                'vat_dung_id' => (int)$data['vat_dung_id'],
                'type' => $type,
                'description' => $data['description'] ?? null,
                'fee' => isset($data['fee']) ? (float)$data['fee'] : null,
                'reported_by' => $data['reported_by'],
                'reported_at' => now(),
            ]);

            // if instance, update its status
            if ($instance && !empty($data['mark_instance_status'])) {
                $mapInst = ['damaged' => PhongVatDungInstance::STATUS_DAMAGED, 'missing' => PhongVatDungInstance::STATUS_MISSING, 'lost' => PhongVatDungInstance::STATUS_LOST];
                $instance->status = $mapInst[$data['mark_instance_status']] ?? $instance->status;
                $instance->save();
            }

            // create / attach invoice item if booking present
            if (!empty($datPhongId)) {
                $vat = VatDung::find($incident->vat_dung_id);
                $unitPrice = $incident->fee ?? ($vat->gia ?? 0);
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

                // update hoaDon total
                $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $amount;
                $hoaDon->save();

                // update pivot phong_vat_dung
                $pivot = DB::table('phong_vat_dung')->where('phong_id', $incident->phong_id)->where('vat_dung_id', $incident->vat_dung_id)->lockForUpdate()->first();
                if ($pivot) {
                    DB::table('phong_vat_dung')->where('phong_id', $incident->phong_id)->where('vat_dung_id', $incident->vat_dung_id)
                        ->update([
                            'so_luong' => max(0, ((int)$pivot->so_luong) - $qty),
                            'da_tieu_thu' => ((int)$pivot->da_tieu_thu) + $qty,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $incident->phong_id,
                        'vat_dung_id' => $incident->vat_dung_id,
                        'so_luong' => 0,
                        'da_tieu_thu' => $qty,
                        'gia_override' => null,
                        'tracked_instances' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $incident->billed_at = now();
                $incident->save();

                $dp = DB::table('dat_phong')->where('id', $datPhongId)->lockForUpdate()->first();
                if ($dp) {
                    $newTotal = (float)($dp->tong_tien ?? 0) + $amount;
                    DB::table('dat_phong')->where('id', $datPhongId)->update(['tong_tien' => $newTotal, 'updated_at' => now()]);
                }
            }

            DB::commit();
            return back()->with('success', 'Ghi nhận sự cố thành công' . (!empty($datPhongId) ? ' và đã tính vào hoá đơn.' : '.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Không thể ghi nhận sự cố: ' . $e->getMessage()]);
        }
    }

    public function destroy(\App\Models\DatPhong $booking, VatDungIncident $incident)
    {
        if ($incident->dat_phong_id !== $booking->id) {
            return back()->withErrors(['error' => 'Sự cố không thuộc booking này.']);
        }

        DB::beginTransaction();
        try {
            $items = HoaDonItem::where('type', 'incident')->where('ref_id', $incident->id)->get();
            foreach ($items as $it) {
                $hoaDon = HoaDon::find($it->hoa_don_id);
                $amount = (float)$it->amount;
                $qty = (int)$it->quantity;

                // revert pivot
                $pivot = DB::table('phong_vat_dung')->where('phong_id', $incident->phong_id)->where('vat_dung_id', $incident->vat_dung_id)->lockForUpdate()->first();
                if ($pivot) {
                    DB::table('phong_vat_dung')->where('phong_id', $incident->phong_id)->where('vat_dung_id', $incident->vat_dung_id)
                        ->update([
                            'so_luong' => ((int)$pivot->so_luong) + $qty,
                            'da_tieu_thu' => max(0, ((int)$pivot->da_tieu_thu) - $qty),
                            'updated_at' => now()
                        ]);
                }

                if ($hoaDon) {
                    $hoaDon->tong_thuc_thu = max(0, (float)$hoaDon->tong_thuc_thu - $amount);
                    $hoaDon->save();
                    $it->delete();

                    $remaining = HoaDonItem::where('hoa_don_id', $hoaDon->id)->count();
                    if ($remaining === 0 && $hoaDon->trang_thai !== 'da_thanh_toan') {
                        $hoaDon->delete();
                    }
                } else {
                    $it->delete();
                }

                $dp = DB::table('dat_phong')->where('id', $booking->id)->lockForUpdate()->first();
                if ($dp) {
                    DB::table('dat_phong')->where('id', $booking->id)->update(['tong_tien' => max(0, ((float)$dp->tong_tien - $amount)), 'updated_at' => now()]);
                }
            }

            if ($incident->phong_vat_dung_instance_id) {
                try {
                    $inst = PhongVatDungInstance::find($incident->phong_vat_dung_instance_id);
                    if ($inst) {
                        $inst->status = \App\Models\PhongVatDungInstance::STATUS_PRESENT;
                        $inst->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Could not revert instance status when deleting incident #' . $incident->id . ': ' . $e->getMessage());
                }
            }

            $incident->delete();

            DB::commit();
            return back()->with('success', 'Xóa sự cố và hoàn trả hoá đơn (nếu có) thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Không thể xóa sự cố: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, DatPhong $booking, VatDungIncident $incident)
    {
        $this->authorize('update', $incident);
        $data = $request->validate([
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
        ]);
        $incident->update($data);
        return back()->with('success', 'Cập nhật sự cố thành công');
    }
}
