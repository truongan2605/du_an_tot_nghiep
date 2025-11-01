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
            'status' => 'nullable|in:ok,lost,damaged,used,archived',
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

        // ensure pivot exists
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

        $activeBooking = $phong->activeDatPhong();
        if ($activeBooking && in_array($activeBooking->trang_thai, ['da_dat', 'dang_su_dung'])) {
            return back()->withErrors([
                'error' => 'Không thể tạo/đổi cấu trúc bản thể khi phòng đang có booking ở trạng thái "da_dat" hoặc "dang_su_dung".'
            ]);
        }

        DB::transaction(function () use ($phong, $vatDung, $data, $quantity) {
            $instance = PhongVatDungInstance::create([
                'phong_id' => $phong->id,
                'vat_dung_id' => $vatDung->id,
                'serial' => $data['serial'] ?? null,
                'status' => $data['status'] ?? 'present',
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'quantity' => $quantity,
            ]);

            // bump pivot so_luong so UI that reads pivot shows correct count
            DB::table('phong_vat_dung')
                ->where('phong_id', $phong->id)
                ->where('vat_dung_id', $vatDung->id)
                ->increment('so_luong', $quantity);
        });

        return back()->with('success', 'Tạo instance thành công');
    }



    // update serial/note/status etc.
    public function update(Request $request, PhongVatDungInstance $instance)
    {
        $data = $request->validate([
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|in: present, damaged, missing',
        ]);

        $instance->fill($data);
        $instance->save();

        return back()->with('success', 'Cập nhật bản thể thành công');
    }

    public function updateStatus(Request $request, PhongVatDungInstance $instance)
    {
        $data = $request->validate([
            'status' => 'required|in:present,damaged,missing',
            'create_consumption' => 'nullable|boolean',
            'incident_fee' => 'nullable|numeric|min:0',
            'incident_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($instance, $data) {
            $old = $instance->status;
            $instance->status = $data['status'];
            $instance->save();

            if (in_array($data['status'], ['damaged', 'missing'])) {
                \App\Models\VatDungIncident::create([
                    'phong_vat_dung_instance_id' => $instance->id,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'type' => $data['status'],
                    'description' => $data['incident_note'] ?? ('Status changed to ' . $data['status']),
                    'fee' => isset($data['incident_fee']) ? (float) $data['incident_fee'] : (optional($instance->vatDung)->gia ?? 0),
                    'reported_by' => Auth::id(),
                    // billed_at stays null until invoice/billing
                ]);
            }

            // Tùy chọn: tạo consumption để charge ngay (ví dụ admin muốn ghi vào hóa đơn phòng)
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
            }
        });

        return back()->with('success', 'Cập nhật trạng thái thành công');
    }


    // mark lost convenience endpoint (đổi status + optional create consumption)
    public function markLost(Request $request, PhongVatDungInstance $instance)
    {
        $createConsumption = $request->boolean('create_consumption', false);

        DB::transaction(function () use ($instance, $createConsumption) {
            $instance->status = 'lost';
            $instance->save();

            if ($createConsumption) {
                PhongVatDungConsumption::create([
                    'dat_phong_id' => null,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'quantity' => 1,
                    'unit_price' => optional($instance->vatDung)->gia ?? 0,
                    'note' => 'Charge for lost instance id=' . $instance->id,
                    'created_by' => Auth::id(),
                    'consumed_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Đánh dấu mất thành công');
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
