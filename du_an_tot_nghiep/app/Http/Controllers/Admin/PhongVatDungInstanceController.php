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
            'status' => 'nullable|in:present,damaged,missing,lost,archived',
            'quantity' => 'nullable|integer|min:1',
        ]);

        // prevent creating/changing instances if room has active booking
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

            // bump pivot so_luong (use lock)
            DB::table('phong_vat_dung')
                ->where('phong_id', $phong->id)
                ->where('vat_dung_id', $vatDung->id)
                ->lockForUpdate()
                ->increment('so_luong', $quantity);
        });

        return back()->with('success', 'Tạo instance thành công');
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
                \App\Models\VatDungIncident::create([
                    'phong_vat_dung_instance_id' => $instance->id,
                    'phong_id' => $instance->phong_id,
                    'vat_dung_id' => $instance->vat_dung_id,
                    'type' => $data['status'],
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
                // If charging immediately, also increment da_tieu_thu and decrement so_luong atomically:
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
                            'so_luong' => max(0, ((int)$pivot->so_luong) - 1),
                            'da_tieu_thu' => ((int)$pivot->da_tieu_thu) + 1,
                            'updated_at' => now()
                        ]);
                }
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
