<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\PhongVatDungInstance;
use App\Models\VatDung;
use App\Models\PhongVatDungConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\VatDungIncident;


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

    public function store(Request $request, Phong $phong)
    {
        $data = $request->validate([
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|in:present,damaged,missing,lost,archived',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $activeBooking = $phong->activeDatPhong();
        if ($activeBooking && in_array($activeBooking->trang_thai, ['da_dat', 'dang_su_dung'])) {
            return back()->withErrors(['error' => 'Không thể tạo hoặc thay đổi cấu trúc bản thể khi phòng đang được đặt hoặc đang sử dụng.']);
        }

        $quantity = (int) ($data['quantity'] ?? 1);

        $vatDung = VatDung::find($data['vat_dung_id']);
        if (!$vatDung) {
            return back()->withErrors(['error' => 'Vật dụng không tồn tại.']);
        }

        if ($vatDung->isConsumable()) {
            return back()->withErrors(['error' => 'Không nên tạo instance cho vật dụng kiểu consumable (do_an).']);
        }

        if (! (bool) $vatDung->tracked_instances) {
            return back()->withErrors(['error' => 'Vật dụng này không được bật "theo dõi bản" (tracked_instances). Không thể tạo bản thể.']);
        }

        $allowed = $phong->loaiPhong ? $phong->loaiPhong->vatDungs->pluck('id')->toArray() : [];
        if (!in_array($vatDung->id, $allowed)) {
            return back()->withErrors(['error' => 'Vật dụng này không được cấu hình cho Loại phòng của phòng hiện tại. Vui lòng thêm vào Loại phòng trước.']);
        }

        DB::transaction(function () use ($phong, $vatDung, $data, $quantity) {
            $instance = PhongVatDungInstance::create([
                'phong_id' => $phong->id,
                'vat_dung_id' => $vatDung->id,
                'serial' => $data['serial'] ?? null,
                'status' => $data['status'] ?? PhongVatDungInstance::STATUS_PRESENT,
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'quantity' => $quantity,
            ]);

            $exists = DB::table('phong_vat_dung')
                ->where('phong_id', $phong->id)
                ->where('vat_dung_id', $vatDung->id)
                ->exists();

            if (! $exists) {
                DB::table('phong_vat_dung')->insert([
                    'phong_id' => $phong->id,
                    'vat_dung_id' => $vatDung->id,
                    'so_luong' => 0,
                    'da_tieu_thu' => 0,
                    'gia_override' => null,
                    'tracked_instances' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('phong_vat_dung')
                ->where('phong_id', $phong->id)
                ->where('vat_dung_id', $vatDung->id)
                ->lockForUpdate()
                ->increment('so_luong', $quantity);
        });

        return back()->with('success', 'Tạo vật dụng thành công');
    }


    public function update(Request $request, PhongVatDungInstance $instance)
    {
        $data = $request->validate([
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|in:present,damaged,missing',
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

        DB::beginTransaction();
        try {
            $old = $instance->status;
            $new = $data['status'];

            $activeBooking = $instance->phong?->activeDatPhong() ?? null;
            $activeBookingId = $activeBooking->id ?? null;

            $instance->status = $new;
            $instance->save();

            if ($new === 'present') {
                $incQuery = \App\Models\VatDungIncident::where('phong_vat_dung_instance_id', $instance->id)
                    ->where('phong_id', $instance->phong_id);
                if ($activeBookingId) $incQuery->where('dat_phong_id', $activeBookingId);

                $incidents = $incQuery->get();

                foreach ($incidents as $inc) {
                    $items = \App\Models\HoaDonItem::where('type', 'incident')
                        ->where('ref_id', $inc->id)
                        ->get();

                    foreach ($items as $it) {
                        $hoaDon = \App\Models\HoaDon::find($it->hoa_don_id);
                        $amount = (float)$it->amount;
                        $qty = (int)$it->quantity;

                        $pivot = DB::table('phong_vat_dung')
                            ->where('phong_id', $inc->phong_id)
                            ->where('vat_dung_id', $inc->vat_dung_id)
                            ->lockForUpdate()
                            ->first();

                        if ($pivot) {
                            $newSoLuong = ((int)$pivot->so_luong) + $qty;
                            $newDaTieu = max(0, ((int)$pivot->da_tieu_thu) - $qty);
                            DB::table('phong_vat_dung')
                                ->where('phong_id', $inc->phong_id)
                                ->where('vat_dung_id', $inc->vat_dung_id)
                                ->update([
                                    'so_luong' => $newSoLuong,
                                    'da_tieu_thu' => $newDaTieu,
                                    'updated_at' => now()
                                ]);
                        }

                        if ($hoaDon) {
                            $hoaDon->tong_thuc_thu = max(0, (float)$hoaDon->tong_thuc_thu - $amount);
                            $hoaDon->save();
                            $it->delete();

                            $remaining = \App\Models\HoaDonItem::where('hoa_don_id', $hoaDon->id)->count();
                            if ($remaining === 0 && $hoaDon->trang_thai !== 'da_thanh_toan') {
                                $hoaDon->delete();
                            }
                        } else {
                            $it->delete();
                        }

                        // if (!empty($inc->dat_phong_id)) {
                        //     $dp = DB::table('dat_phong')->where('id', $inc->dat_phong_id)->lockForUpdate()->first();
                        //     if ($dp && isset($dp->tong_tien)) {
                        //         $newTotal = max(0, ((float)$dp->tong_tien) - $amount);
                        //         DB::table('dat_phong')->where('id', $inc->dat_phong_id)
                        //             ->update(['tong_tien' => $newTotal, 'updated_at' => now()]);
                        //     }
                        // }
                    }

                    $inc->delete();
                }
            }

            if (in_array($new, ['damaged', 'missing'])) {
                if ($old !== 'present') {
                    throw new \Exception('Chỉ được ghi nhận hỏng/mất khi bản thể đang ở trạng thái "Nguyên vẹn".');
                }

                $existQ = \App\Models\VatDungIncident::where('phong_vat_dung_instance_id', $instance->id)
                    ->where('phong_id', $instance->phong_id);
                if ($activeBookingId) $existQ->where('dat_phong_id', $activeBookingId);

                if ($existQ->exists()) {
                    throw new \Exception('Đã có sự cố / hoá đơn tương ứng cho bản thể này trong booking hiện tại.');
                }

                $mapType = [
                    'damaged' => 'damage',
                    'missing' => 'loss',
                ];
                $incidentType = $mapType[$new] ?? 'other';

                VatDungIncident::create([
                    'phong_vat_dung_instance_id' => $instance->id,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'dat_phong_id' => $activeBookingId,
                    'type' => $incidentType,
                    'description' => $data['incident_note'] ?? ('Status changed to ' . $new),
                    'fee' => isset($data['incident_fee']) ? (float) $data['incident_fee'] : (optional($instance->vatDung)->gia ?? 0),
                    'reported_by' => Auth::id(),
                    'reported_at' => now(),
                ]);
            }

            if (!empty($data['create_consumption']) && in_array($new, ['damaged', 'missing'])) {
                PhongVatDungConsumption::create([
                    'dat_phong_id' => $activeBookingId,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'quantity' => (int) ($instance->quantity ?? 1),
                    'unit_price' => optional($instance->vatDung)->gia ?? 0,
                    'note' => 'Auto-charge due to instance status ' . $new . ' (instance id=' . $instance->id . ')',
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

            DB::commit();
            return back()->with('success', 'Cập nhật trạng thái thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi: ' . $e->getMessage()]);
        }
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
