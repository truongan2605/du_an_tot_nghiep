<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use Illuminate\Http\Request;
use App\Models\PhongVatDungConsumption;
use App\Models\VatDung;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PhongConsumptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'phong_id' => 'required|exists:phong,id',
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            // optional flag to bill immediately from admin UI
            'bill_now' => 'nullable|boolean',
        ]);

        // verify dat_phong actually relates to phong (dat_phong_item or giu_phong)
        $belongs = DB::table('dat_phong_item')
            ->where('dat_phong_id', $data['dat_phong_id'])
            ->where('phong_id', $data['phong_id'])
            ->exists();

        if (! $belongs) {
            // fallback: check giu_phong if table exists
            if (Schema::hasTable('giu_phong')) {
                $belongs = DB::table('giu_phong')
                    ->where('dat_phong_id', $data['dat_phong_id'])
                    ->where('phong_id', $data['phong_id'])
                    ->exists();
            }
        }

        if (! $belongs) {
            return back()->withErrors(['error' => 'Phòng không thuộc booking được chỉ định.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $qty = (int)$data['quantity'];
            $vat = VatDung::find($data['vat_dung_id']);
            $unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : (float)($vat->gia ?? 0);

            // Lock pivot row
            $pivot = DB::table('phong_vat_dung')
                ->where('phong_id', $data['phong_id'])
                ->where('vat_dung_id', $data['vat_dung_id'])
                ->lockForUpdate()
                ->first();

            if ($pivot) {
                $currentSo = (int)($pivot->so_luong ?? 0);
                $currentDaTieu = (int)($pivot->da_tieu_thu ?? 0);

                $newSoLuong = max(0, $currentSo - $qty);
                $newDaTieu = $currentDaTieu + $qty;

                DB::table('phong_vat_dung')
                    ->where('phong_id', $data['phong_id'])
                    ->where('vat_dung_id', $data['vat_dung_id'])
                    ->update(['so_luong' => $newSoLuong, 'da_tieu_thu' => $newDaTieu, 'updated_at' => now()]);
            } else {
                if ($vat && $vat->isConsumable()) {
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $data['phong_id'],
                        'vat_dung_id' => $data['vat_dung_id'],
                        'so_luong' => 0,
                        'da_tieu_thu' => $qty,
                        'gia_override' => null,
                        'tracked_instances' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Vật dụng này chưa được cấu hình cho phòng (không thể tiêu thụ).'])->withInput();
                }
            }

            $cons = PhongVatDungConsumption::create([
                'dat_phong_id' => $data['dat_phong_id'],
                'phong_id' => $data['phong_id'],
                'vat_dung_id' => $data['vat_dung_id'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'consumed_at' => now(),
                'billed_at' => null,
            ]);

            $billNow = $request->boolean('bill_now');

            if ($billNow) {
                $amount = round($qty * $unitPrice, 2);

                $datPhongRow = DB::table('dat_phong')
                    ->where('id', $data['dat_phong_id'])
                    ->lockForUpdate()
                    ->first();

                $hoaDon = DB::table('hoa_don')
                    ->where('dat_phong_id', $data['dat_phong_id'])
                    ->where('trang_thai', '!=', 'da_thanh_toan')
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                if (! $hoaDon) {
                    $now = now();
                    $hoaDonId = DB::table('hoa_don')->insertGetId([
                        'dat_phong_id' => $data['dat_phong_id'],
                        'so_hoa_don' => 'HD' . time(),
                        'tong_thuc_thu' => 0,
                        'don_vi' => 'VND',
                        'trang_thai' => 'tao',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $hoaDon = DB::table('hoa_don')->where('id', $hoaDonId)->first();
                }

                $now = now();
                DB::table('hoa_don_items')->insert([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'consumption',
                    'ref_id' => $cons->id,
                    'vat_dung_id' => $vat->id ?? null,
                    'name' => $vat->ten ?? 'Item',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'note' => $data['note'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('hoa_don')->where('id', $hoaDon->id)
                    ->update(['tong_thuc_thu' => DB::raw('COALESCE(tong_thuc_thu,0) + ' . $amount), 'updated_at' => now()]);

                $cons->billed_at = now();
                $cons->save();

                if ($datPhongRow) {
                    DB::table('dat_phong')->where('id', $data['dat_phong_id'])
                        ->update(['tong_tien' => DB::raw('COALESCE(tong_tien,0) + ' . $amount), 'updated_at' => now()]);
                }
            }

            DB::commit();
            return back()->with('success', 'Đã ghi nhận tiêu thụ thành công.' . ($billNow ? ' Và đã tính vào hóa đơn.' : ''));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi khi lưu tiêu thụ: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, PhongVatDungConsumption $consumption)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($consumption->billed_at) {
            return back()->withErrors(['error' => 'Đã tính hoá đơn, không thể chỉnh.']);
        }

        DB::beginTransaction();
        try {
            $oldQty = (int)$consumption->quantity;
            $newQty = (int)$data['quantity'];
            $diff = $newQty - $oldQty; // >0: tăng tiêu thụ, <0: giảm tiêu thụ

            if ($diff !== 0) {
                // lock pivot
                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $consumption->phong_id)
                    ->where('vat_dung_id', $consumption->vat_dung_id)
                    ->lockForUpdate()
                    ->first();

                if ($pivot) {
                    $currentSo = (int)($pivot->so_luong ?? 0);
                    $currentDaTieu = (int)($pivot->da_tieu_thu ?? 0);

                    if ($diff > 0) {
                        // increase consumption -> decrease so_luong, increase da_tieu_thu
                        $newSo = max(0, $currentSo - $diff);
                        $newDa = $currentDaTieu + $diff;
                    } else {
                        // decrease consumption -> return stock (increase so_luong), decrease da_tieu_thu
                        $newSo = $currentSo - $diff; // diff negative so -diff => add
                        $newDa = max(0, $currentDaTieu + $diff); // reduce da_tieu_thu
                    }

                    // If $newDa not set (when diff >0 we set), ensure set for update
                    if (!isset($newDa)) $newDa = $currentDaTieu;
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $consumption->phong_id)
                        ->where('vat_dung_id', $consumption->vat_dung_id)
                        ->update(['so_luong' => $newSo, 'da_tieu_thu' => $newDa, 'updated_at' => now()]);
                } else {
                    // No pivot: if decreasing consumption, just update consumption and create pivot if needed
                    if ($diff < 0) {
                        DB::table('phong_vat_dung')->insert([
                            'phong_id' => $consumption->phong_id,
                            'vat_dung_id' => $consumption->vat_dung_id,
                            'so_luong' => -$diff, // returned stock
                            'da_tieu_thu' => max(0, $oldQty + $diff),
                            'gia_override' => null,
                            'tracked_instances' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        // If trying to increase consumption but pivot missing and item non-consumable -> error
                        $vat = VatDung::find($consumption->vat_dung_id);
                        if ($vat && ! $vat->isConsumable()) {
                            DB::rollBack();
                            return back()->withErrors(['error' => 'Không thể tăng tiêu thụ: vật dụng không phải consumable và chưa có pivot.']);
                        }
                        // otherwise create pivot with da_tieu_thu = diff
                        DB::table('phong_vat_dung')->insert([
                            'phong_id' => $consumption->phong_id,
                            'vat_dung_id' => $consumption->vat_dung_id,
                            'so_luong' => 0,
                            'da_tieu_thu' => $diff,
                            'gia_override' => null,
                            'tracked_instances' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $consumption->update([
                'quantity' => $newQty,
                'unit_price' => $data['unit_price'] ?? $consumption->unit_price,
                'note' => $data['note'] ?? $consumption->note,
            ]);

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

            // nếu là consumable và có phong_id -> tăng da_tieu_thu & giảm so_luong
            $vat = $consumption->vatDung;
            if ($vat && $vat->isConsumable() && $consumption->phong_id) {
                $pivot = DB::table('phong_vat_dung')
                    ->where('phong_id', $consumption->phong_id)
                    ->where('vat_dung_id', $vat->id)
                    ->lockForUpdate()
                    ->first();

                if ($pivot) {
                    $newSo = max(0, ((int)$pivot->so_luong) - (int)$consumption->quantity);
                    $newDa = ((int)$pivot->da_tieu_thu) + (int)$consumption->quantity;
                    DB::table('phong_vat_dung')
                        ->where('phong_id', $consumption->phong_id)
                        ->where('vat_dung_id', $vat->id)
                        ->update(['so_luong' => $newSo, 'da_tieu_thu' => $newDa, 'updated_at' => now()]);
                } else {
                    // create pivot as consumed
                    DB::table('phong_vat_dung')->insert([
                        'phong_id' => $consumption->phong_id,
                        'vat_dung_id' => $vat->id,
                        'so_luong' => 0,
                        'da_tieu_thu' => (int)$consumption->quantity,
                        'gia_override' => null,
                        'tracked_instances' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
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
            return back()->withErrors(['error' => 'Đã tính hoá đơn, không thể xóa.']);
        }

        DB::beginTransaction();
        try {
            $qty = (int)$consumption->quantity;

            $pivot = DB::table('phong_vat_dung')
                ->where('phong_id', $consumption->phong_id)
                ->where('vat_dung_id', $consumption->vat_dung_id)
                ->lockForUpdate()
                ->first();

            if ($pivot) {
                // trả kho: so_luong += qty, da_tieu_thu = max(0, da_tieu_thu - qty)
                $newSo = ((int)$pivot->so_luong) + $qty;
                $newDa = max(0, ((int)$pivot->da_tieu_thu) - $qty);
                DB::table('phong_vat_dung')
                    ->where('phong_id', $consumption->phong_id)
                    ->where('vat_dung_id', $consumption->vat_dung_id)
                    ->update(['so_luong' => $newSo, 'da_tieu_thu' => $newDa, 'updated_at' => now()]);
            } else {
                DB::table('phong_vat_dung')->insert([
                    'phong_id' => $consumption->phong_id,
                    'vat_dung_id' => $consumption->vat_dung_id,
                    'so_luong' => $qty,
                    'da_tieu_thu' => 0,
                    'gia_override' => null,
                    'tracked_instances' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $consumption->delete();

            DB::commit();
            return back()->with('success', 'Xóa thành công và trả kho.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi xóa: ' . $e->getMessage()]);
        }
    }

    public function storeAndBill(Request $request)
    {
        $data = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'phong_id' => 'required|exists:phong,id',
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        $datPhong = \App\Models\DatPhong::findOrFail($data['dat_phong_id']);

        if (! $datPhong->canSetupConsumables()) {
            return back()->withErrors(['error' => 'Booking không hợp lệ để setup consumables.']);
        }

        DB::beginTransaction();
        try {
            $qty = (int)$data['quantity'];
            $vat = VatDung::find($data['vat_dung_id']);
            $unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : (float)($vat->gia ?? 0);
            $amount = round($qty * $unitPrice, 2);

            // --- 0) Lock và tính "Vật phẩm ban đầu" (reservation chưa consumed & chưa billed)
            $reservationQty = (int) DB::table('phong_vat_dung_consumptions')
                ->where('dat_phong_id', $datPhong->id)
                ->where('phong_id', $data['phong_id'])
                ->where('vat_dung_id', $data['vat_dung_id'])
                ->whereNull('consumed_at')
                ->whereNull('billed_at')
                ->lockForUpdate()
                ->sum('quantity');

            // --- 1) Tính "Vật phẩm đã tiêu thụ (hóa đơn)" (từ hoa_don_items của dat_phong, loại consumption)
            $invoicedQty = (int) DB::table('hoa_don_items')
                ->join('hoa_don', 'hoa_don_items.hoa_don_id', '=', 'hoa_don.id')
                ->where('hoa_don.dat_phong_id', $datPhong->id)
                // loại hoá đơn đã huỷ không tính
                ->where('hoa_don.trang_thai', '!=', 'da_huy')
                ->where('hoa_don_items.type', 'consumption')
                ->where('hoa_don_items.vat_dung_id', $data['vat_dung_id'])
                ->select(DB::raw('COALESCE(SUM(hoa_don_items.quantity),0) as qty'))
                ->value('qty');

            // --- 2) Remaining available to bill
            $remaining = max(0, $reservationQty - $invoicedQty);

            if ($qty > $remaining) {
                DB::rollBack();
                return back()->withErrors([
                    'error' => "Không thể tiêu thụ $qty. Hiện chỉ còn $remaining (Vật phẩm ban đầu: $reservationQty, đã tính vào hoá đơn: $invoicedQty)."
                ])->withInput();
            }

            // --- 4) Lấy hoặc tạo hoá đơn
            $hoaDon = HoaDon::where('dat_phong_id', $datPhong->id)
                ->where('trang_thai', '!=', 'da_thanh_toan')
                ->orderBy('id', 'desc')
                ->first();

            if (! $hoaDon) {
                $hoaDon = HoaDon::create([
                    'dat_phong_id' => $datPhong->id,
                    'so_hoa_don' => 'HD' . time(),
                    'tong_thuc_thu' => 0,
                    'don_vi' => 'VND',
                    'trang_thai' => 'tao',
                ]);
            }

            // --- 5) Thêm item vào hoá đơn (chỉ item ở đây, ref_id null như yêu cầu)
            $item = HoaDonItem::create([
                'hoa_don_id' => $hoaDon->id,
                'type' => 'consumption',
                'ref_id' => null,
                'vat_dung_id' => $vat->id ?? null,
                'name' => $vat->ten ?? 'Item',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'note' => $data['note'] ?? null,
            ]);

            // --- 6) Cập nhật tổng hoá đơn
            $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $amount;
            $hoaDon->save();

            DB::commit();
            return redirect()->route('admin.phong.show', $data['phong_id'])->with('success', 'Đã ghi nhận tiêu thụ và tính vào hoá đơn.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi khi xử lý: ' . $e->getMessage()]);
        }
    }
}
