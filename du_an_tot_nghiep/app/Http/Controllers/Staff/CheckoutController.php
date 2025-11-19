<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use App\Models\Phong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    private function buildRoomItemFromDatPhongItem($hoaDonId, $datPhongItem, $booking, $nights)
    {
        $qty = (int)($datPhongItem->so_luong ?? 1);
        $unit = (float)($datPhongItem->gia_tren_dem ?? 0);
        $amount = $unit * $qty * max(1, $nights);

        $name = 'Phòng';
        if (!empty($datPhongItem->phong_ma)) {
            $name .= ' — ' . $datPhongItem->phong_ma;
        } else {
            $name .= ' — Booking #' . ($booking->ma_tham_chieu ?? $booking->id);
        }

        $item = [
            'hoa_don_id' => $hoaDonId,
            'type' => 'room_booking',
            'ref_id' => $datPhongItem->id ?? null,
            'name' => $name,
            'quantity' => $qty,
            'unit_price' => $unit,
            'amount' => $amount,
            'note' => 'Lưu lịch sử phòng khi checkout',
        ];

        // --- IMPORTANT: set phong_id & loai_phong_id if dat_phong_item carries them ---
        if (property_exists($datPhongItem, 'phong_id') && !empty($datPhongItem->phong_id)) {
            $item['phong_id'] = $datPhongItem->phong_id;
        }
        if (property_exists($datPhongItem, 'loai_phong_id') && !empty($datPhongItem->loai_phong_id)) {
            $item['loai_phong_id'] = $datPhongItem->loai_phong_id;
        }

        return $item;
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

            $nights = 1;
            if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
                $nights = Carbon::parse($booking->ngay_nhan_phong)
                    ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
                $nights = max(1, $nights);
            }

            // TH1: KHÔNG có khoản phát sinh & KHÔNG có hoá đơn chưa thanh toán
            if (empty($unpaidIds) && $extrasTotal <= 0) {
                $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();

                $hoaDon = HoaDon::create([
                    'dat_phong_id' => $booking->id,
                    'so_hoa_don' => 'HD' . time(),
                    'tong_thuc_thu' => 0,
                    'don_vi' => $booking->don_vi_tien ?? 'VND',
                    'trang_thai' => $markPaid ? 'da_thanh_toan' : 'da_xuat',
                    'created_by' => Auth::id() ?? null,
                ]);

                if ($markPaid) {
                    foreach ($datPhongItems as $it) {
                        $roomItem = $this->buildRoomItemFromDatPhongItem($hoaDon->id, $it, $booking, $nights);
                        HoaDonItem::create($roomItem);
                    }
                }

                $hoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
                $hoaDon->save();

                if ($markPaid) {
                    $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                    if (!empty($phongIds)) {
                        Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
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
                return redirect()->route('staff.bookings.checkout.show', ['booking' => $booking->id, 'invoice' => $hoaDon->id])
                    ->with('success', 'Hoá đơn đã được lập (chờ thanh toán): #' . $hoaDon->id);
            }

            // TH2: Có khoản phát sinh / hoá đơn chưa thanh toán -> sử dụng hoá đơn hiện có 
            $targetHoaDon = $unpaidHoaDons->first();

            if (count($unpaidIds) > 1) {
                $otherIds = array_values(array_diff($unpaidIds, [$targetHoaDon->id]));
                if (!empty($otherIds)) {
                    HoaDonItem::whereIn('hoa_don_id', $otherIds)->update(['hoa_don_id' => $targetHoaDon->id]);
                    HoaDon::whereIn('id', $otherIds)->delete();
                }
            }

            $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();

            if ($markPaid) {
                foreach ($datPhongItems as $it) {
                    $exists = HoaDonItem::where('hoa_don_id', $targetHoaDon->id)
                        ->where('type', 'room_booking')
                        ->where('ref_id', $it->id ?? null)
                        ->exists();

                    if (!$exists) {
                        $roomItem = $this->buildRoomItemFromDatPhongItem($targetHoaDon->id, $it, $booking, $nights);
                        HoaDonItem::create($roomItem);
                    }
                }
            }

            if ($markPaid) {
                $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->trang_thai = 'da_thanh_toan';
                $targetHoaDon->save();

                $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                }

                DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

                $booking->checkout_at = now();
                $booking->trang_thai = 'hoan_thanh';
                $booking->save();

                DB::commit();

                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', 'Checkout hoàn tất — hoá đơn #' . $targetHoaDon->id . ' đã được đánh dấu là đã thanh toán.');
            }

            $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
            $targetHoaDon->trang_thai = 'da_xuat';
            $targetHoaDon->save();

            DB::commit();

            return redirect()->route('staff.bookings.checkout.show', ['booking' => $booking->id, 'invoice' => $targetHoaDon->id])
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

            $nights = 1;
            if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
                $nights = Carbon::parse($booking->ngay_nhan_phong)
                    ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
                $nights = max(1, $nights);
            }

            $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
            foreach ($datPhongItems as $it) {
                $exists = HoaDonItem::where('hoa_don_id', $hoaDon->id)
                    ->where('type', 'room_booking')
                    ->where('ref_id', $it->id ?? null)
                    ->exists();

                if (!$exists) {
                    $roomItem = $this->buildRoomItemFromDatPhongItem($hoaDon->id, $it, $booking, $nights);
                    HoaDonItem::create($roomItem);
                }
            }

            $hoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
            $hoaDon->trang_thai = 'da_thanh_toan';
            $hoaDon->save();

            $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
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
