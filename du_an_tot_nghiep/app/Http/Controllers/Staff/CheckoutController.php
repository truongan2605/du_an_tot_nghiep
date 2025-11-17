<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use App\Models\Phong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function showCheckoutForm(DatPhong $booking)
    {
        $booking->load(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'nguoiDung']);

        $nights = 1;
        if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
            $nights = Carbon::parse($booking->ngay_nhan_phong)
                ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
            $nights = max(1, $nights);
        }

        $roomLines = [];
        $roomsTotal = 0;
        foreach ($booking->datPhongItems as $item) {
            $unitPrice = $item->gia_tren_dem ?? 0;
            $qty = $item->so_luong ?? 1;
            $lineTotal = $unitPrice * $qty * $nights;
            $roomLines[] = [
                'phong_id' => $item->phong_id,
                'ma_phong' => $item->phong?->ma_phong ?? null,
                'loai' => $item->loaiPhong?->ten ?? null,
                'unit_price' => $unitPrice,
                'qty' => $qty,
                'nights' => $nights,
                'line_total' => $lineTotal,
            ];
            $roomsTotal += $lineTotal;
        }

        $unpaidHoaDons = HoaDon::where('dat_phong_id', $booking->id)
            ->where('trang_thai', '!=', 'da_thanh_toan')
            ->get();

        $extrasItems = [];
        $extrasTotal = 0;
        if ($unpaidHoaDons->isNotEmpty()) {
            $unpaidIds = $unpaidHoaDons->pluck('id')->toArray();
            $items = HoaDonItem::whereIn('hoa_don_id', $unpaidIds)->get();

            foreach ($items as $it) {
                $extrasItems[] = [
                    'hoa_don_id' => $it->hoa_don_id,
                    'name' => $it->name ?? ($it->vatDung?->ten ?? 'Item'),
                    'quantity' => $it->quantity ?? 1,
                    'unit_price' => $it->unit_price ?? ($it->amount / max(1, ($it->quantity ?? 1))),
                    'amount' => (float) ($it->amount ?? 0),
                    'billed' => false,
                ];
                $extrasTotal += (float) ($it->amount ?? 0);
            }
        }

        // totals
        $discount = $booking->discount_amount ?? 0;
        $roomSnapshot = $booking->snapshot_total ?? $roomsTotal;
        $deposit = (float) ($booking->deposit_amount ?? 0);

        $amountToPayNow = max(0, $extrasTotal);

        $address = ' Tòa nhà FPT Polytechnic., Cổng số 2, 13 Trịnh Văn Bô, Xuân Phương, Nam Từ Liêm, Hà Nội';

        return view('staff.bookings.checkout_preview', compact(
            'booking',
            'roomLines',
            'roomsTotal',
            'extrasItems',
            'extrasTotal',
            'discount',
            'roomSnapshot',
            'deposit',
            'amountToPayNow',
            'address'
        ));
    }


    // Xử lý khi confirm checkout
    public function processCheckout(Request $request, DatPhong $booking)
    {
        $data = $request->validate([
            'mark_paid' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $unpaidHoaDons = HoaDon::where('dat_phong_id', $booking->id)
                ->where('trang_thai', '!=', 'da_thanh_toan')
                ->orderByDesc('id')
                ->get();

            $unpaidIds = $unpaidHoaDons->pluck('id')->toArray();

            // tổng phát sinh từ các hoá đơn chưa thanh toán
            $existingItems = collect();
            $extrasTotal = 0;
            if (!empty($unpaidIds)) {
                $existingItems = HoaDonItem::whereIn('hoa_don_id', $unpaidIds)->get();
                foreach ($existingItems as $it) {
                    $extrasTotal += (float)($it->amount ?? 0);
                }
            }

            $markPaid = !empty($data['mark_paid']);

            //  TH1: KHÔNG có khoản phát sinh & KHÔNG có hoá đơn chưa thanh toán 
            if (empty($unpaidIds) && $extrasTotal <= 0) {
                $roomAmount = (float) ($booking->tong_tien ?? 0);
                if ($roomAmount <= 0) {
                    $roomAmount = (float) ($booking->snapshot_total ?? 0);
                }
                if ($roomAmount <= 0) {
                    $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
                    $nights = 1;
                    if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
                        $nights = Carbon::parse($booking->ngay_nhan_phong)
                            ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
                        $nights = max(1, $nights);
                    }
                    $calc = 0;
                    foreach ($datPhongItems as $it) {
                        $unit = (float)($it->gia_tren_dem ?? 0);
                        $qty = (int)($it->so_luong ?? 1);
                        $calc += $unit * $qty * $nights;
                    }
                    $roomAmount = $calc;
                }

                // tạo hoá đơn 
                $hoaDon = HoaDon::create([
                    'dat_phong_id' => $booking->id,
                    'so_hoa_don' => 'HD' . time(),
                    'tong_thuc_thu' => 0,
                    'don_vi' => $booking->don_vi_tien ?? 'VND',
                    'trang_thai' => $markPaid ? 'da_thanh_toan' : 'da_xuat',
                    'created_by' => Auth::id() ?? null,
                ]);

                if ($roomAmount > 0) {
                    HoaDonItem::create([
                        'hoa_don_id' => $hoaDon->id,
                        'type' => 'room',
                        'ref_id' => $booking->id,
                        'vat_dung_id' => null,
                        'name' => 'Phòng — Booking #' . $booking->ma_tham_chieu,
                        'quantity' => 1,
                        'unit_price' => $roomAmount,
                        'amount' => $roomAmount,
                        'note' => 'Snapshot tổng phòng (tạo khi checkout)',
                    ]);
                }

                $hoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
                $hoaDon->save();

                if ($markPaid) {
                    $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
                    $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
                    if (!empty($phongIds)) {
                        Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'updated_at' => now()]);
                    }

                    DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

                    $booking->checkout_at = now();
                    $booking->trang_thai = 'hoan_thanh';
                    $booking->save();

                    DB::commit();
                    return redirect()->route('staff.bookings.show', $booking->id)
                        ->with('success', 'Checkout hoàn tất — hoá đơn #' . $hoaDon->id . ' đã được lập và đánh dấu là đã thanh toán.');
                }

                DB::commit();
                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', 'Hoá đơn đã được lập (chờ thanh toán): #' . $hoaDon->id);
            } // end TH1

            //  TH2: Có khoản phát sinh / hoá đơn chưa thanh toán -> sử dụng hoá đơn hiện có 
            $targetHoaDon = $unpaidHoaDons->first();

            // gom items từ các hoá đơn khác vào target nếu có nhiều
            if (count($unpaidIds) > 1) {
                $otherIds = array_values(array_diff($unpaidIds, [$targetHoaDon->id]));
                if (!empty($otherIds)) {
                    HoaDonItem::whereIn('hoa_don_id', $otherIds)->update(['hoa_don_id' => $targetHoaDon->id]);
                    HoaDon::whereIn('id', $otherIds)->delete();
                }
            }

            if ($markPaid) {
                // xác định roomAmount ưu tiên dat_phong.tong_tien -> snapshot -> compute
                $roomAmount = (float) ($booking->tong_tien ?? 0);
                if ($roomAmount <= 0) {
                    $roomAmount = (float) ($booking->snapshot_total ?? 0);
                }
                if ($roomAmount <= 0) {
                    $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
                    $nights = 1;
                    if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
                        $nights = Carbon::parse($booking->ngay_nhan_phong)
                            ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
                        $nights = max(1, $nights);
                    }
                    $calc = 0;
                    foreach ($datPhongItems as $it) {
                        $unit = (float)($it->gia_tren_dem ?? 0);
                        $qty = (int)($it->so_luong ?? 1);
                        $calc += $unit * $qty * $nights;
                    }
                    $roomAmount = $calc;
                }

                // thêm dòng room nếu chưa có
                $hasRoomLine = HoaDonItem::where('hoa_don_id', $targetHoaDon->id)
                    ->where('type', 'room')
                    ->where('ref_id', $booking->id)
                    ->exists();

                if (!$hasRoomLine && $roomAmount > 0) {
                    HoaDonItem::create([
                        'hoa_don_id' => $targetHoaDon->id,
                        'type' => 'room',
                        'ref_id' => $booking->id,
                        'vat_dung_id' => null,
                        'name' => 'Phòng — Booking #' . $booking->ma_tham_chieu,
                        'quantity' => 1,
                        'unit_price' => $roomAmount,
                        'amount' => $roomAmount,
                        'note' => 'Snapshot tổng phòng (thêm khi finalize checkout)',
                    ]);
                }

                // cập nhật tổng hoá đơn
                $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->trang_thai = 'da_thanh_toan';
                $targetHoaDon->save();

                // finalize: release rooms, delete dat_phong_item, update booking
                $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
                $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'updated_at' => now()]);
                }

                DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

                $booking->checkout_at = now();
                $booking->trang_thai = 'hoan_thanh';
                $booking->save();

                DB::commit();

                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', 'Checkout hoàn tất — hoá đơn #' . $targetHoaDon->id . ' đã được đánh dấu là đã thanh toán.');
            }

            // nếu staff chưa tick mark_paid -> chỉ đặt hoá đơn target về da_xuat (đợi thanh toán)
            $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
            $targetHoaDon->trang_thai = 'da_xuat';
            $targetHoaDon->save();

            DB::commit();

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Hoá đơn đã được xuất (chờ thanh toán): #' . $targetHoaDon->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Không thể checkout: ' . $e->getMessage()]);
        }
    }

    public function confirmPayment(Request $request, DatPhong $booking, HoaDon $hoaDon)
    {
        DB::beginTransaction();
        try {
            if ((int)$hoaDon->dat_phong_id !== (int)$booking->id) {
                return back()->withErrors(['error' => 'Hoá đơn không thuộc booking này.']);
            }

            if ($hoaDon->trang_thai === 'da_thanh_toan') {
                return back()->with('info', 'Hoá đơn đã được đánh dấu là đã thanh toán trước đó.');
            }

            if (!in_array($hoaDon->trang_thai, ['da_xuat', 'tao'])) {
                return back()->withErrors(['error' => 'Không thể confirm vì trạng thái hoá đơn hiện tại không hợp lệ.']);
            }

            // ensure room line exists with booking->tong_tien (ưu tiên) or snapshot_total
            $roomAmount = (float) ($booking->tong_tien ?? $booking->snapshot_total ?? 0);
            $hasRoomLine = HoaDonItem::where('hoa_don_id', $hoaDon->id)
                ->where('type', 'room')
                ->where('ref_id', $booking->id)
                ->exists();

            if (!$hasRoomLine && $roomAmount > 0) {
                HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'room',
                    'ref_id' => $booking->id,
                    'vat_dung_id' => null,
                    'name' => 'Phòng — Booking #' . $booking->ma_tham_chieu,
                    'quantity' => 1,
                    'unit_price' => $roomAmount,
                    'amount' => $roomAmount,
                    'note' => 'Snapshot tổng phòng (thêm khi xác nhận thanh toán)',
                ]);
            }

            // cập nhật tổng hoá đơn từ items (room + extras)
            $hoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
            $hoaDon->trang_thai = 'da_thanh_toan';
            $hoaDon->save();

            // finalize checkout after payment confirmed
            $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
            $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'updated_at' => now()]);
            }

            DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

            $booking->checkout_at = now();
            $booking->trang_thai = 'hoan_thanh';
            $booking->save();

            DB::commit();

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Hoá đơn #' . $hoaDon->id . ' đã được đánh dấu là đã thanh toán và checkout hoàn tất.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Không thể xác nhận thanh toán: ' . $e->getMessage()]);
        }
    }
}
