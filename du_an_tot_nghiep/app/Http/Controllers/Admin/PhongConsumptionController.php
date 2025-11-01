<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhongVatDungConsumption;
use App\Models\VatDung;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PhongConsumptionController extends Controller
{
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'phong_id' => 'nullable|exists:phong,id',
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        $datPhong = \App\Models\DatPhong::find($data['dat_phong_id']);
        if (!$datPhong) {
            return back()->withErrors(['error' => 'Booking không tồn tại.'])->withInput();
        }

        if ($datPhong->trang_thai !== 'dang_su_dung') {
            return back()->withErrors(['error' => 'Chỉ được thêm tiêu thụ khi booking đang ở trạng thái "dang_su_dung".'])->withInput();
        }

        DB::beginTransaction();
        try {
            $vat = VatDung::find($data['vat_dung_id']);
            $qty = (int)$data['quantity'];
            $unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : ($vat ? (float)($vat->gia ?? 0) : 0);
            $phongId = $data['phong_id'] ?? null;
            if (!$phongId && method_exists($datPhong, 'datPhongItems')) {
                $item = $datPhong->datPhongItems()->whereNotNull('phong_id')->first();
                if ($item) {
                    $phongId = $item->phong_id;
                }
            }

            // Tìm reservation (chưa consumed, chưa billed)
            $existingReserved = PhongVatDungConsumption::where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $phongId)
                ->where('vat_dung_id', $vat->id)
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->first();

            $createdRecords = [];

            if ($existingReserved) {
                // trường hợp có reservation: xử lý tách/convert tùy qty
                if ($existingReserved->quantity > $qty) {
                    // giảm reservation và tạo 1 record consumed cho phần tiêu thụ
                    $existingReserved->quantity = $existingReserved->quantity - $qty;
                    $existingReserved->save();

                    $cons = PhongVatDungConsumption::create([
                        'dat_phong_id' => $datPhong->id,
                        'phong_id' => $phongId,
                        'vat_dung_id' => $vat->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'note' => $data['note'] ?? null,
                        'created_by' => Auth::id(),
                        'consumed_at' => now(),
                    ]);
                    $createdRecords[] = $cons;
                } else {
                    // consume toàn bộ reservation (và có thể thêm extra nếu request > reserved)
                    $consumeQty = $existingReserved->quantity;
                    $existingReserved->consumed_at = now();
                    if (isset($data['unit_price'])) $existingReserved->unit_price = $unitPrice;
                    if (!empty($data['note'])) $existingReserved->note = $data['note'];
                    $existingReserved->save();
                    $createdRecords[] = $existingReserved;

                    $remaining = $qty - $consumeQty;
                    if ($remaining > 0) {
                        $extra = PhongVatDungConsumption::create([
                            'dat_phong_id' => $datPhong->id,
                            'phong_id' => $phongId,
                            'vat_dung_id' => $vat->id,
                            'quantity' => $remaining,
                            'unit_price' => $unitPrice,
                            'note' => $data['note'] ?? null,
                            'created_by' => Auth::id(),
                            'consumed_at' => now(),
                        ]);
                        $createdRecords[] = $extra;
                    }
                }
            } else {
                // không có reservation: tạo record consumed mới
                $cons = PhongVatDungConsumption::create([
                    'dat_phong_id' => $datPhong->id,
                    'phong_id' => $phongId,
                    'vat_dung_id' => $vat->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'note' => $data['note'] ?? null,
                    'created_by' => Auth::id(),
                    'consumed_at' => now(),
                ]);
                $createdRecords[] = $cons;
            }

            // Nếu item consumable (do_an) và có pivot so_luong trong phong_vat_dung -> giảm số lượng pivot
            if ($vat && $vat->isConsumable() && $phongId) {
                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $phongId)
                    ->where('vat_dung_id', $vat->id)
                    ->first();

                $totalConsumed = array_sum(array_map(fn($r) => $r->quantity, $createdRecords));

                if ($pivot && isset($pivot->so_luong)) {
                    $new = max(0, ((int)$pivot->so_luong) - $totalConsumed);
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $phongId)
                        ->where('vat_dung_id', $vat->id)
                        ->update(['so_luong' => $new]);
                }
            }

            DB::commit();
            return back()->with('success', 'Thêm món tiêu thụ thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi khi lưu tiêu thụ: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, PhongVatDungConsumption $consumption)
    {
        $this->authorize('update', $consumption); // optional

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($consumption->billed_at) {
            return back()->withErrors(['error' => 'Món này đã được tính hoá đơn, không thể chỉnh.']);
        }

        DB::beginTransaction();
        try {
            $oldQty = (int)$consumption->quantity;
            $newQty = (int)$data['quantity'];
            $vat = $consumption->vatDung;

            // cập nhật bản ghi
            $consumption->update([
                'quantity' => $newQty,
                'unit_price' => $data['unit_price'] ?? $consumption->unit_price,
                'note' => $data['note'] ?? $consumption->note,
            ]);

            // nếu đây là consumed record (consumed_at not null) và vat là consumable -> điều chỉnh pivot so_luong (bù/ghi)
            if ($consumption->consumed_at && $vat && $vat->isConsumable() && $consumption->phong_id) {
                $diff = $newQty - $oldQty; // dương => giảm thêm pivot; âm => trả lại pivot
                if ($diff !== 0) {
                    $pivot = DB::table('phong_vat_dung')
                        ->where('phong_id', $consumption->phong_id)
                        ->where('vat_dung_id', $vat->id)
                        ->first();
                    if ($pivot && isset($pivot->so_luong)) {
                        $new = max(0, ((int)$pivot->so_luong) - $diff);
                        DB::table('phong_vat_dung')
                            ->where('phong_id', $consumption->phong_id)
                            ->where('vat_dung_id', $vat->id)
                            ->update(['so_luong' => $new]);
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Cập nhật thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi cập nhật: ' . $e->getMessage()]);
        }
    }

    public function markConsumed(Request $request, PhongVatDungConsumption $consumption)
    {
        if ($consumption->consumed_at) {
            return back()->withErrors(['error' => 'Đã được đánh dấu tiêu thụ trước đó.']);
        }

        $data = $request->validate([
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $consumption->consumed_at = now();
            if (isset($data['unit_price'])) {
                $consumption->unit_price = (float)$data['unit_price'];
            }
            $consumption->save();

            // nếu là consumable và có phong_id -> giảm pivot so_luong
            $vat = $consumption->vatDung;
            if ($vat && $vat->isConsumable() && $consumption->phong_id) {
                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $consumption->phong_id)
                    ->where('vat_dung_id', $vat->id)
                    ->first();

                if ($pivot && isset($pivot->so_luong)) {
                    $new = max(0, ((int)$pivot->so_luong) - (int)$consumption->quantity);
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $consumption->phong_id)
                        ->where('vat_dung_id', $vat->id)
                        ->update(['so_luong' => $new]);
                }
            }

            DB::commit();
            return back()->with('success', 'Đã đánh dấu là đã tiêu thụ.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi khi đánh dấu tiêu thụ: ' . $e->getMessage()]);
        }
    }



    public function destroy(PhongVatDungConsumption $consumption)
    {
        if ($consumption->billed_at) {
            return back()->withErrors(['error' => 'Món này đã được tính hoá đơn, không thể xóa.']);
        }

        DB::beginTransaction();
        try {
            $vat = $consumption->vatDung;
            $phongId = $consumption->phong_id;
            $qty = (int)$consumption->quantity;

            // nếu đây là consumed record và là consumable thì trả lại pivot
            if ($consumption->consumed_at && $vat && $vat->isConsumable() && $phongId) {
                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $phongId)
                    ->where('vat_dung_id', $vat->id)
                    ->first();
                if ($pivot && isset($pivot->so_luong)) {
                    $new = ((int)$pivot->so_luong) + $qty;
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $phongId)
                        ->where('vat_dung_id', $vat->id)
                        ->update(['so_luong' => $new]);
                }
            }

            $consumption->delete();

            DB::commit();
            return back()->with('success', 'Xóa thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi xóa: ' . $e->getMessage()]);
        }
    }
}
