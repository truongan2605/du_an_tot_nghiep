<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use App\Models\HoaDon;
use App\Models\HoaDonItem;
use App\Models\Phong;
use App\Models\GiaoDich;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\PaymentNotificationService;
use App\Services\MoMoPaymentService;
use App\Mail\InvoiceMail;
use App\Models\DatPhongItemHistory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function showCheckoutForm(DatPhong $booking)
    {
        $booking->load(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'nguoiDung']);

        // nights
        $nights = 1;
        if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
            $nights = Carbon::parse($booking->ngay_nhan_phong)
                ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
            $nights = max(1, $nights);
        }

  $roomLines = [];
$roomsTotal = 0;

$checkIn = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
$checkOut = \Carbon\Carbon::parse($booking->ngay_tra_phong);
$totalNights = $checkIn->diffInDays($checkOut);

$weekendNights = 0;
$current = $checkIn->copy();
while ($current->lt($checkOut)) {
    $dayOfWeek = $current->dayOfWeek;
    if ($dayOfWeek == \Carbon\Carbon::FRIDAY || 
        $dayOfWeek == \Carbon\Carbon::SATURDAY || 
        $dayOfWeek == \Carbon\Carbon::SUNDAY) {
        $weekendNights++;
    }
    $current->addDay();
}
$weekdayNights = $totalNights - $weekendNights;

// ✅ TÍNH VOUCHER
$totalRooms = $booking->datPhongItems->count() ?: 1;
$voucherPerRoom = 0;
if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
    $voucherPerRoom = (float)$booking->discount_amount / $totalRooms;
} elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
    $voucherPerRoom = (float)$booking->voucher_discount / $totalRooms;
}

foreach ($booking->datPhongItems as $item) {
    $basePrice = $item->phong->tong_gia ?? 0;
    
    $extraAdults = $item->number_adult ?? 0;
    $extraChildren = $item->number_child ?? 0;
    $extraCharge = ($extraAdults * 150000) + ($extraChildren * 60000);
    
    $pricePerNight = $basePrice + $extraCharge;
    
    $weekdayTotal = $pricePerNight * $weekdayNights;
    
    $weekendBaseTotal = $basePrice * $weekendNights;
    $weekendSurcharge = $basePrice * 0.1 * $weekendNights;
    $weekendExtraTotal = $extraCharge * $weekendNights;
    $weekendTotal = $weekendBaseTotal + $weekendSurcharge + $weekendExtraTotal;
    
    $lineTotalPerRoom = $weekdayTotal + $weekendTotal;
    
    $qty = $item->so_luong ?? 1;
    
    // ✅ TRƯỚC VOUCHER
    $lineTotal = $lineTotalPerRoom * $qty;
    
    // ✅ SAU VOUCHER (cho 1 phòng, không nhân qty)
    $lineTotalAfterVoucher = $lineTotal - $voucherPerRoom;

    
    
    $roomLines[] = [
        'phong_id'   => $item->phong_id,
        'ma_phong'   => $item->phong?->ma_phong ?? null,
        'loai'       => $item->loaiPhong?->ten ?? null,
        'base_price' => $basePrice,
        'extra_charge' => $extraCharge,
        'extra_adults' => $extraAdults,
        'extra_children' => $extraChildren,
        'weekend_surcharge' => $weekendSurcharge,
        'weekend_nights' => $weekendNights,
        'voucher_per_room' => $voucherPerRoom, // ✅ Thêm voucher
        'qty'        => $qty,
        'nights'     => $totalNights,
        'line_total' => round($lineTotal), // ✅ Trước voucher
        'line_total_after_voucher' => round($lineTotalAfterVoucher), // ✅ Sau voucher
    ];
    
    $roomsTotal += $lineTotalAfterVoucher; // ✅ Cộng giá SAU voucher
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

        $discount = $booking->discount_amount ?? 0;
        $roomSnapshot = $booking->snapshot_total ?? $roomsTotal;
        $deposit = (float) ($booking->deposit_amount ?? 0);
        $amountToPayNow = max(0, $extrasTotal);

        $address = ' Tòa nhà FPT Polytechnic, Cổng số 2, 13 Trịnh Văn Bô, Xuân Phương, Nam Từ Liêm, Hà Nội';

        $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
        $dailyTotal = collect($datPhongItems)->reduce(function ($carry, $it) {
            $qty = $it->so_luong ?? 1;
            $unit = $it->gia_tren_dem ?? 0;
            return $carry + ($unit * $qty);
        }, 0.0);

        $now = Carbon::now();
        $origCheckoutDate = $booking->ngay_tra_phong ? Carbon::parse($booking->ngay_tra_phong)->setTime(12, 0) : null;

        // --- Early checkout eligibility & estimate ---
        $earlyEligible = false;
        $earlyDays = 0;
        $earlyRefundEstimate = 0;
        if ($origCheckoutDate && $origCheckoutDate->greaterThan($now)) {
            $hoursDiff = $now->diffInHours($origCheckoutDate);
            $earlyDays = (int) floor($hoursDiff / 24);
            $earlyEligible = $hoursDiff >= 24 && $earlyDays >= 1;
            if ($earlyEligible) {
                $earlyRefundEstimate = (int) round(0.5 * $dailyTotal * $earlyDays, 0);
            }
        }

        // --- Late checkout ---
        $lateEligible = false;
        $lateHoursFull = 0;
        $lateMinutesRemainder = 0;
        $lateHoursFloat = 0.0;
        $lateFeeEstimate = 0;
        if ($origCheckoutDate && $now->greaterThan($origCheckoutDate)) {
            $totalLateMinutes = $origCheckoutDate->diffInMinutes($now);
            $lateHoursFull = (int) floor($totalLateMinutes / 60);
            $lateMinutesRemainder = $totalLateMinutes % 60;
            $lateHoursFloat = $totalLateMinutes / 60.0;
            if ($lateHoursFull >= 1) {
                $lateEligible = true;
                $perHour = $dailyTotal / 24.0;
                $lateFeeEstimate = (int) round($perHour * $lateHoursFull, 0);
            }
        }

        $earlyNet = $earlyRefundEstimate - $extrasTotal;
        $earlyNetIsRefund = $earlyNet >= 0;
        $earlyNetDisplay = (int) round(abs($earlyNet), 0);

        $lateNet = $lateFeeEstimate + $extrasTotal;
        $lateNetDisplay = (int) round($lateNet, 0);

        $roomIdsForBooking = collect($booking->datPhongItems)->pluck('phong_id')->filter()->unique()->values()->all();

        $nextBookings = collect();
        $blockingNextBooking = null;

        if (!empty($roomIdsForBooking)) {
            $nextBookings = \App\Models\DatPhong::whereHas('datPhongItems', function ($q) use ($roomIdsForBooking) {
                $q->whereIn('phong_id', $roomIdsForBooking);
            })
                ->where('id', '!=', $booking->id)
                ->whereNotIn('trang_thai', ['da_huy'])
                ->whereNotNull('ngay_nhan_phong')
                ->where('ngay_nhan_phong', '>=', $booking->ngay_tra_phong ?? now()->toDateString())
                ->orderBy('ngay_nhan_phong', 'asc')
                ->get();

            $blockingNextBooking = $nextBookings->first();
        }

        $hasNextBooking = $nextBookings->isNotEmpty();
        $blockingNextBookingStart = null;
        if ($blockingNextBooking && !empty($blockingNextBooking->ngay_nhan_phong)) {
            $blockingNextBookingStart = \Carbon\Carbon::parse($blockingNextBooking->ngay_nhan_phong);
        }

        // ✅ LẤY LỊCH SỬ ĐỔI PHÒNG
$changeRoomHistory = \App\Models\LichSuDoiPhong::where('dat_phong_id', $booking->id)
    ->with(['phongCu', 'phongMoi'])
    ->orderBy('created_at', 'desc')
    ->get();

$totalRoomChangeDiff = 0; // Tổng chênh lệch từ đổi phòng

foreach ($changeRoomHistory as $history) {
    $diff = $history->gia_moi - $history->gia_cu;
    $totalRoomChangeDiff += $diff;
}

// ✅ TÍNH SỐ TIỀN THỰC TẾ CẦN THU
$actualAmountToPay = ($extrasTotal ?? 0) + $totalRoomChangeDiff;


        // Check for ANY pending online payment transactions
        $pendingPayment = GiaoDich::where('dat_phong_id', $booking->id)
            ->where('trang_thai', 'dang_cho')
            ->latest()
            ->first();

        return view('staff.bookings.checkout_preview', compact(
            'booking',
            'roomLines',
            'roomsTotal',
            'extrasItems',
            'changeRoomHistory', // ✅ Thêm
    'totalRoomChangeDiff', // ✅ Thêm
    'actualAmountToPay', // ✅ Thêm
            'extrasTotal',
            'discount',
            'roomSnapshot',
            'deposit',
            'amountToPayNow',
            'address',
            'dailyTotal',
            'earlyEligible',
            'earlyDays',
            'earlyRefundEstimate',
            'lateEligible',
            'lateHoursFull',
            'lateMinutesRemainder',
            'lateHoursFloat',
            'lateFeeEstimate',
            'earlyNet',
            'earlyNetIsRefund',
            'earlyNetDisplay',
            'lateNetDisplay',
            'nextBookings',
            'blockingNextBooking',
            'hasNextBooking',
            'blockingNextBookingStart',
            'pendingPayment',
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

        if (property_exists($datPhongItem, 'phong_id') && !empty($datPhongItem->phong_id)) {
            $item['phong_id'] = $datPhongItem->phong_id;
        }
        if (property_exists($datPhongItem, 'loai_phong_id') && !empty($datPhongItem->loai_phong_id)) {
            $item['loai_phong_id'] = $datPhongItem->loai_phong_id;
        }

        return $item;
    }

    // hítory item
    private function archiveDatPhongItems(int $bookingId)
{
    $cartItems = DB::table('dat_phong_item')->where('dat_phong_id', $bookingId)->get();

    $created = collect();
    foreach ($cartItems as $it) {
        // map tên cột theo db của bạn: nếu cột tên khác, sửa lại
        $payload = [
            'dat_phong_id' => $it->dat_phong_id ?? $bookingId,
            'phong_id' => $it->phong_id ?? null,
            'phong_ma' => $it->phong_ma ?? null,
            'loai_phong_id' => $it->loai_phong_id ?? null,
            'gia_tren_dem' => $it->gia_tren_dem ?? ($it->gia ?? null),
            'so_luong' => $it->so_luong ?? 1,
            'snapshot' => null,
            'created_at' => $it->created_at ?? now(),
            'updated_at' => $it->updated_at ?? now(),
        ];

        $createdItem = DatPhongItemHistory::create($payload);
        $created->push($createdItem);
    }

    return $created;
}

    /**
     * Xóa ảnh CCCD từ snapshot_meta của booking
     */
    private function deleteCCCDImages(DatPhong $booking)
    {
        $snapshotMeta = $booking->snapshot_meta;
        if (is_array($snapshotMeta)) {
            $meta = $snapshotMeta;
        } elseif (is_string($snapshotMeta) && !empty($snapshotMeta)) {
            $decoded = json_decode($snapshotMeta, true);
            $meta = is_array($decoded) ? $decoded : [];
        } else {
            $meta = [];
        }

        // Xóa tất cả CCCD trong danh sách
        if (!empty($meta['checkin_cccd_list']) && is_array($meta['checkin_cccd_list'])) {
            foreach ($meta['checkin_cccd_list'] as $cccdItem) {
                if (!empty($cccdItem['front']) && Storage::disk('public')->exists($cccdItem['front'])) {
                    Storage::disk('public')->delete($cccdItem['front']);
                }
                if (!empty($cccdItem['back']) && Storage::disk('public')->exists($cccdItem['back'])) {
                    Storage::disk('public')->delete($cccdItem['back']);
                }
            }
        }

        // Xóa ảnh cũ (backward compatibility)
        if (!empty($meta['checkin_cccd']) && Storage::disk('public')->exists($meta['checkin_cccd'])) {
            Storage::disk('public')->delete($meta['checkin_cccd']);
        }
        if (!empty($meta['checkin_cccd_front']) && Storage::disk('public')->exists($meta['checkin_cccd_front'])) {
            Storage::disk('public')->delete($meta['checkin_cccd_front']);
        }
        if (!empty($meta['checkin_cccd_back']) && Storage::disk('public')->exists($meta['checkin_cccd_back'])) {
            Storage::disk('public')->delete($meta['checkin_cccd_back']);
        }

        // Xóa thông tin ảnh khỏi snapshot_meta
        unset($meta['checkin_cccd']);
        unset($meta['checkin_cccd_front']);
        unset($meta['checkin_cccd_back']);
        unset($meta['checkin_cccd_list']);

        // Cập nhật lại snapshot_meta
        $booking->update(['snapshot_meta' => $meta]);
    }

    // Xử lý khi confirm checkout
    public function processCheckout(Request $request, DatPhong $booking)
    {
        $data = $request->validate([
            'mark_paid' => 'nullable|boolean',
            'early_checkout' => 'nullable|boolean',
            'action' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $unpaidHoaDons = HoaDon::where('dat_phong_id', $booking->id)
                ->where('trang_thai', '!=', 'da_thanh_toan')
                ->orderByDesc('id')
                ->get();

            $unpaidIds = $unpaidHoaDons->pluck('id')->toArray();

            $existingItems = collect();
            $extrasTotal = 0;
            if (!empty($unpaidIds)) {
                $existingItems = HoaDonItem::whereIn('hoa_don_id', $unpaidIds)->get();
                foreach ($existingItems as $it) {
                    $extrasTotal += (float)($it->amount ?? 0);
                }
            }

            $isEarlyRequested = ($data['action'] ?? '') === 'early_checkout';
            $isLateRequested = ($data['action'] ?? '') === 'late_checkout';

            $markPaid = $isEarlyRequested || $isLateRequested ? true : (!empty($data['mark_paid']));

            // nights & daily total
            $nights = 1;
            if ($booking->ngay_nhan_phong && $booking->ngay_tra_phong) {
                $nights = Carbon::parse($booking->ngay_nhan_phong)
                    ->diffInDays(Carbon::parse($booking->ngay_tra_phong));
                $nights = max(1, $nights);
            }

            $datPhongItems = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->get();
            $dailyTotal = collect($datPhongItems)->reduce(function ($carry, $it) {
                $qty = $it->so_luong ?? 1;
                $unit = $it->gia_tren_dem ?? 0;
                return $carry + ($unit * $qty);
            }, 0.0);

            // original checkout datetime at 12:00
            $now = Carbon::now();
            $origCheckoutDate = $booking->ngay_tra_phong ? Carbon::parse($booking->ngay_tra_phong)->setTime(12, 0) : null;

            // --- EARLY CHECKOUT FLOW ---
            if ($isEarlyRequested) {
                $earlyRefund = 0;
                $earlyDays = 0;
                $eligible = false;
                if ($origCheckoutDate && $origCheckoutDate->greaterThan($now)) {
                    $hoursDiff = $now->diffInHours($origCheckoutDate);
                    $earlyDays = (int) floor($hoursDiff / 24);
                    $eligible = $hoursDiff >= 24 && $earlyDays >= 1;
                    if ($eligible) {
                        $earlyRefund = (int) round(0.5 * $dailyTotal * $earlyDays, 0);
                    }
                }

                if (! $eligible) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Checkout sớm không hợp lệ (không đủ thời gian trước thời điểm checkout chuẩn).']);
                }

                $targetHoaDon = $unpaidHoaDons->first();
                if (! $targetHoaDon) {
                    $targetHoaDon = HoaDon::create([
                        'dat_phong_id' => $booking->id,
                        'so_hoa_don' => 'HD' . time(),
                        'tong_thuc_thu' => 0,
                        'don_vi' => $booking->don_vi_tien ?? 'VND',
                        'trang_thai' => 'da_xuat',
                        'created_by' => Auth::id() ?? null,
                    ]);
                }

                foreach ($datPhongItems as $it) {
                    $exists = HoaDonItem::where('hoa_don_id', $targetHoaDon->id)
                        ->where('type', 'room_booking')
                        ->where('ref_id', $it->id ?? null)
                        ->exists();

                    if (! $exists) {
                        $roomItem = $this->buildRoomItemFromDatPhongItem($targetHoaDon->id, $it, $booking, $nights);
                        HoaDonItem::create($roomItem);
                    }
                }

                if ($earlyRefund > 0) {
                    HoaDonItem::create([
                        'hoa_don_id' => $targetHoaDon->id,
                        'type' => 'refund',
                        'name' => 'Hoàn tiền checkout sớm',
                        'quantity' => 1,
                        'unit_price' => -1 * $earlyRefund,
                        'amount' => -1 * $earlyRefund,
                        'note' => 'Refund for early checkout',
                    ]);
                }

                $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->trang_thai = $markPaid ? 'da_thanh_toan' : 'da_xuat';
                $targetHoaDon->save();

                $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                }

                // 1) sao chép cart -> history
$this->archiveDatPhongItems($booking->id);

// 2) sau khi đã tạo history, xóa cart (nếu bạn muốn)
DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();


                $booking->checkout_at = now();
                $booking->checkout_by = Auth::id();
                $booking->trang_thai = 'hoan_thanh';
                $booking->blocks_checkin = false;
                $booking->is_checkout_early = true;
                $booking->early_checkout_refund_amount = $earlyRefund > 0 ? $earlyRefund : 0;
                $booking->save();

                // Xóa ảnh CCCD sau khi checkout sớm thành công
                $this->deleteCCCDImages($booking);

                DB::commit();

                // Gửi thông báo checkout sớm
                $notificationService = new PaymentNotificationService();
                $notificationService->sendEarlyCheckoutNotification($booking, $targetHoaDon->id, $earlyDays, $earlyRefund);

                // Gửi email hóa đơn tự động
                $this->sendInvoiceEmail($booking, $targetHoaDon);

                $msg = 'Checkout sớm hoàn tất.';
                if ($earlyRefund > 0) {
                    $msg .= ' Khoản hoàn tiền ước tính: ' . number_format($earlyRefund, 0) . ' ₫ đã được ghi nhận trong hoá đơn.';
                } else {
                    $msg .= ' Không có khoản hoàn tiền (không đủ điều kiện hoàn tiền).';
                }

                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', $msg);
            }

            if ($isLateRequested) {
                if (! $origCheckoutDate) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Không thể tính checkout muộn: chưa có ngày trả phòng gốc.']);
                }

                if (! $now->greaterThan($origCheckoutDate)) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Chưa đến giờ checkout chuẩn, không thể tính checkout muộn.']);
                }

                $totalLateMinutes = $origCheckoutDate->diffInMinutes($now);
                $lateHoursFull = (int) floor($totalLateMinutes / 60);
                $lateMinutesRemainder = $totalLateMinutes % 60;

                if ($lateHoursFull < 1) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Chưa đủ 1 giờ trễ để tính phí checkout muộn.']);
                }

                $perHour = $dailyTotal / 24.0;
                $lateFee = (int) round($perHour * $lateHoursFull, 0); // charge whole hours only

                // prepare invoice
                $targetHoaDon = $unpaidHoaDons->first();
                if (! $targetHoaDon) {
                    $targetHoaDon = HoaDon::create([
                        'dat_phong_id' => $booking->id,
                        'so_hoa_don' => 'HD' . time(),
                        'tong_thuc_thu' => 0,
                        'don_vi' => $booking->don_vi_tien ?? 'VND',
                        'trang_thai' => 'da_xuat',
                        'created_by' => Auth::id() ?? null,
                    ]);
                }

                // add room items if not exist
                foreach ($datPhongItems as $it) {
                    $exists = HoaDonItem::where('hoa_don_id', $targetHoaDon->id)
                        ->where('type', 'room_booking')
                        ->where('ref_id', $it->id ?? null)
                        ->exists();

                    if (! $exists) {
                        $roomItem = $this->buildRoomItemFromDatPhongItem($targetHoaDon->id, $it, $booking, $nights);
                        HoaDonItem::create($roomItem);
                    }
                }

                // add late fee item
                if ($lateFee > 0) {
                    HoaDonItem::create([
                        'hoa_don_id' => $targetHoaDon->id,
                        'type' => 'late_fee',
                        'name' => 'Phí checkout muộn',
                        'quantity' => 1,
                        'unit_price' => $lateFee,
                        'amount' => $lateFee,
                        'note' => sprintf('Tính %s giờ muộn%s', $lateHoursFull, $lateMinutesRemainder ? ' + ' . $lateMinutesRemainder . ' phút (không tính phần phút)' : ''),
                    ]);
                }

                // finalize invoice
                $tongThucThu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->tong_thuc_thu = $tongThucThu > 0 ? number_format($tongThucThu, 2, '.', '') : '0.00';
                $targetHoaDon->trang_thai = $markPaid ? 'da_thanh_toan' : 'da_xuat';
                $targetHoaDon->save();

                // free rooms & delete dat_phong_item
                $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                }

                // 1) sao chép cart -> history
$this->archiveDatPhongItems($booking->id);

// 2) sau khi đã tạo history, xóa cart (nếu bạn muốn)
DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();


                $booking->checkout_at = now();
                $booking->checkout_by = Auth::id();
                $booking->trang_thai = 'hoan_thanh';
                $booking->blocks_checkin = false;
                $booking->is_late_checkout = true;
                $booking->setAttribute('late_checkout_fee_amount', $lateFee > 0 ? $lateFee : 0);
                $booking->save();

                DB::commit();

                // Gửi email hóa đơn tự động
                $this->sendInvoiceEmail($booking, $targetHoaDon);

                $msg = 'Checkout muộn hoàn tất.';
                if ($lateFee > 0) {
                    $msg .= ' Khoản phí checkout muộn: ' . number_format($lateFee, 0) . ' ₫ đã được ghi nhận trong hoá đơn.';
                }

                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', $msg);
            }

            // --- NO early/late: fallback to original TH1/TH2 logic ---
            // (same flow as you had originally)
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

                $tongThucThu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
                $hoaDon->tong_thuc_thu = $tongThucThu > 0 ? number_format($tongThucThu, 2, '.', '') : '0.00';
                $hoaDon->save();

                if ($markPaid) {
                    $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                    if (!empty($phongIds)) {
                        Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                    }

                    // 1) sao chép cart -> history
$this->archiveDatPhongItems($booking->id);

// 2) sau khi đã tạo history, xóa cart (nếu bạn muốn)
DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();


                    $booking->checkout_at = now();
                    $booking->checkout_by = Auth::id();
                    $booking->trang_thai = 'hoan_thanh';
                    $booking->blocks_checkin = false;
                    $booking->save();

                    // Xóa ảnh CCCD sau khi checkout thành công
                    $this->deleteCCCDImages($booking);

                    DB::commit();

                    // Gửi thông báo checkout
                    $notificationService = new PaymentNotificationService();
                    $notificationService->sendCheckoutNotification($booking, $hoaDon->id);

                    // Gửi email hóa đơn tự động
                    $this->sendInvoiceEmail($booking, $hoaDon);

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
                $tongThucThu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->tong_thuc_thu = $tongThucThu > 0 ? number_format($tongThucThu, 2, '.', '') : '0.00';
                $targetHoaDon->trang_thai = 'da_thanh_toan';
                $targetHoaDon->save();

                $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                }

                // 1) sao chép cart -> history
$this->archiveDatPhongItems($booking->id);

// 2) sau khi đã tạo history, xóa cart (nếu bạn muốn)
DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();


                $booking->checkout_at = now();
                $booking->checkout_by = Auth::id();
                $booking->trang_thai = 'hoan_thanh';
                $booking->blocks_checkin = false;
                $booking->save();

                // Xóa ảnh CCCD sau khi checkout thành công
                $this->deleteCCCDImages($booking);

                DB::commit();

                // Gửi thông báo checkout
                $notificationService = new PaymentNotificationService();
                $notificationService->sendCheckoutNotification($booking, $targetHoaDon->id);

                // Gửi email hóa đơn tự động
                $this->sendInvoiceEmail($booking, $targetHoaDon);

                return redirect()->route('staff.bookings.show', $booking->id)
                    ->with('success', 'Checkout hoàn tất — hoá đơn #' . $targetHoaDon->id . ' đã được đánh dấu là đã thanh toán.');
            }

            $tongThucThu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
            $targetHoaDon->tong_thuc_thu = $tongThucThu > 0 ? number_format($tongThucThu, 2, '.', '') : '0.00';
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

            $tongThucThu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
            $hoaDon->setAttribute('tong_thuc_thu', $tongThucThu > 0 ? $tongThucThu : 0.0);
            $hoaDon->trang_thai = 'da_thanh_toan';
            $hoaDon->save();

            $phongIds = $datPhongItems->pluck('phong_id')->filter()->unique()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
            }

            // 1) sao chép cart -> history
$this->archiveDatPhongItems($booking->id);

// 2) sau khi đã tạo history, xóa cart (nếu bạn muốn)
DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();


            $booking->checkout_at = now();
            $booking->checkout_by = Auth::id();
            $booking->trang_thai = 'hoan_thanh';
            $booking->blocks_checkin = false;
            $booking->save();

            // Xóa ảnh CCCD sau khi checkout thành công
            $this->deleteCCCDImages($booking);

            DB::commit();

            // Gửi thông báo checkout
            $notificationService = new PaymentNotificationService();
            $notificationService->sendCheckoutNotification($booking, $hoaDon->id);

            // Gửi email hóa đơn tự động
            $this->sendInvoiceEmail($booking, $hoaDon);

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Hoá đơn #' . $hoaDon->id . ' đã được đánh dấu là đã thanh toán và checkout hoàn tất.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Không thể xác nhận thanh toán: ' . $e->getMessage()]);
        }
    }

    /**
     * Gửi email hóa đơn tự động cho khách hàng sau khi checkout
     */
    private function sendInvoiceEmail(DatPhong $booking, HoaDon $hoaDon): void
    {
        try {
            // Reload booking với user relationship
            $booking->load(['user']);
            
            // Kiểm tra booking có user và email không
            if (!$booking->user || !$booking->user->email) {
                Log::warning('Cannot send invoice email: missing user or email', [
                    'booking_id' => $booking->id,
                    'hoa_don_id' => $hoaDon->id,
                ]);
                return;
            }

            // Reload hóa đơn với relationships để đảm bảo có đầy đủ dữ liệu
            $hoaDon->load(['hoaDonItems.phong', 'hoaDonItems.loaiPhong', 'hoaDonItems.vatDung']);

            // Gửi email hóa đơn
            Mail::to($booking->user->email)->send(new InvoiceMail($hoaDon, $booking));

            Log::info('Invoice email sent successfully after checkout', [
                'booking_id' => $booking->id,
                'hoa_don_id' => $hoaDon->id,
                'user_id' => $booking->nguoi_dung_id,
                'email' => $booking->user->email,
            ]);
        } catch (\Throwable $e) {
            // Log lỗi nhưng không làm gián đoạn quá trình checkout
            Log::error('Failed to send invoice email after checkout', [
                'booking_id' => $booking->id,
                'hoa_don_id' => $hoaDon->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Initiate online payment redirect (VNPay/MoMo)
     */
    public function initiateOnlinePayment(Request $request, DatPhong $booking)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:vnpay,momo',
            'action' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        $paymentMethod = $validated['payment_method'];
        $amount = (int) round($validated['amount']);
        $action = $validated['action'];

        try {
            DB::beginTransaction();

            // Cancel ALL existing pending transactions for this booking + payment method
            // This ensures no abandoned transactions remain
            $existingPending = GiaoDich::where('dat_phong_id', $booking->id)
                ->where('trang_thai', 'dang_cho')
                ->where('nha_cung_cap', $paymentMethod)
                ->get(); // Get ALL, not just recent ones

            if ($existingPending->isNotEmpty()) {
                foreach ($existingPending as $pending) {
                    $pending->update([
                        'trang_thai' => 'that_bai',
                        'ghi_chu' => $pending->ghi_chu . ' (Tự động hủy - ' . now()->format('d/m/Y H:i') . ')',
                    ]);
                    
                    Log::info('Auto-cancelled abandoned pending transaction', [
                        'transaction_id' => $pending->id,
                        'booking_id' => $booking->id,
                        'age_minutes' => $pending->created_at->diffInMinutes(now()),
                    ]);
                }
            }

            // Create new transaction record
            $transaction = GiaoDich::create([
                'dat_phong_id' => $booking->id,
                'nha_cung_cap' => $paymentMethod,
                'provider_txn_ref' => null,
                'so_tien' => $amount,
                'don_vi' => 'VND',
                'trang_thai' => 'dang_cho',
                'ghi_chu' => "Checkout {$action} - {$booking->ma_tham_chieu}",
            ]);

            // Generate payment URL
            if ($paymentMethod === 'vnpay') {
                $paymentUrl = $this->generateVNPayCheckoutURL($transaction, $booking);
            } else {
                $paymentUrl = $this->generateMoMoCheckoutURL($transaction, $booking);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'payment_url' => $paymentUrl,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout payment init failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateVNPayCheckoutURL(GiaoDich $transaction, DatPhong $booking)
    {
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_ReturnUrl = route('staff.checkout.payment.callback');

        $vnp_TxnRef = 'CHECKOUT_' . $booking->id . '_' . time();
        $vnp_OrderInfo = "Thanh toan checkout - " . $booking->ma_tham_chieu;
        $vnp_Amount = (int) round($transaction->so_tien) * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        $transaction->update(['provider_txn_ref' => $vnp_TxnRef]);

        return $vnp_Url;
    }

    private function generateMoMoCheckoutURL(GiaoDich $transaction, DatPhong $booking)
    {
        $momoService = new MoMoPaymentService();
        
        $orderId = 'CHECKOUT_' . $booking->id . '_' . time();
        $returnUrl = route('staff.checkout.payment.callback');

        $paymentData = $momoService->createPaymentUrl([
            'orderId' => $orderId,
            'amount' => (int) round($transaction->so_tien),
            'orderInfo' => "Thanh toan checkout - " . $booking->ma_tham_chieu,
            'returnUrl' => $returnUrl,
            'notifyUrl' => $returnUrl,
        ]);

        // IMPORTANT: Use the orderId from MoMo response (includes unique suffix)
        $actualOrderId = $paymentData['orderId'] ?? $orderId;
        $transaction->update(['provider_txn_ref' => $actualOrderId]);

        Log::info('MoMo checkout URL generated', [
            'base_order_id' => $orderId,
            'actual_order_id' => $actualOrderId,
            'transaction_id' => $transaction->id,
        ]);

        return $paymentData['payUrl'] ?? $paymentData['deeplink'];
    }

    /**
     * Handle payment callback
     */
    public function handlePaymentCallback(Request $request)
    {
        // Detect provider
        if ($request->has('vnp_TxnRef')) {
            return $this->handleVNPayCheckoutCallback($request);
        } elseif ($request->has('orderId')) {
            return $this->handleMoMoCheckoutCallback($request);
        }

        return redirect()->route('staff.index')->with('error', 'Invalid callback');
    }

    private function handleVNPayCheckoutCallback(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = strtoupper(hash_hmac('sha512', $hashData, $vnp_HashSecret));

        if ($secureHash !== strtoupper($vnp_SecureHash)) {
            Log::error('VNPay checkout signature mismatch');
            return redirect()->route('staff.index')->with('error', 'Xác thực thất bại');
        }

        $vnp_TxnRef = $request->input('vnp_TxnRef');
        $vnp_ResponseCode = $request->input('vnp_ResponseCode');

        $transaction = GiaoDich::where('provider_txn_ref', $vnp_TxnRef)->first();
        if (!$transaction) {
            return redirect()->route('staff.index')->with('error', 'Không tìm thấy giao dịch');
        }

        $booking = DatPhong::find($transaction->dat_phong_id);

        if ($vnp_ResponseCode === '00') {
            $transaction->update(['trang_thai' => 'thanh_cong']);
            
            // Mark invoice as paid and complete checkout
            $this->completeCheckoutAfterPayment($booking);

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Thanh toán thành công!');
        } else {
            $transaction->update(['trang_thai' => 'that_bai']);
            return redirect()->route('staff.bookings.checkout.show', $booking->id)
                ->with('error', 'Thanh toán thất bại');
        }
    }

    private function handleMoMoCheckoutCallback(Request $request)
    {
        Log::info('MoMo checkout callback received', $request->all());

        $momoService = new MoMoPaymentService();
        
        // Try to verify signature, but log and continue if fails (for debugging)
        try {
            if (!$momoService->verifySignature($request->all())) {
                Log::warning('MoMo checkout signature mismatch - continuing anyway');
            }
        } catch (\Exception $e) {
            Log::error('MoMo signature verification error', ['error' => $e->getMessage()]);
        }

        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');

        Log::info('Processing MoMo callback', [
            'orderId' => $orderId,
            'resultCode' => $resultCode
        ]);

        $transaction = GiaoDich::where('provider_txn_ref', $orderId)->first();
        if (!$transaction) {
            Log::error('MoMo transaction not found', ['orderId' => $orderId]);
            return redirect()->route('staff.index')->with('error', 'Không tìm thấy giao dịch');
        }

        $booking = DatPhong::find($transaction->dat_phong_id);

        if ($resultCode == 0) {
            $transaction->update(['trang_thai' => 'thanh_cong']);
            
            $this->completeCheckoutAfterPayment($booking);

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Thanh toán thành công!');
        } else {
            $transaction->update(['trang_thai' => 'that_bai']);
            return redirect()->route('staff.bookings.checkout.show', $booking->id)
                ->with('error', 'Thanh toán thất bại');
        }
    }

    private function completeCheckoutAfterPayment(DatPhong $booking)
    {
        // Mark invoices as paid
        HoaDon::where('dat_phong_id', $booking->id)
            ->where('trang_thai', '!=', 'da_thanh_toan')
            ->update(['trang_thai' => 'da_thanh_toan']);

        // Archive and delete items
        $this->archiveDatPhongItems($booking->id);
        DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

        // Update booking
        $booking->update([
            'checkout_at' => now(),
            'checkout_by' => Auth::id(),
            'trang_thai' => 'hoan_thanh',
            'blocks_checkin' => false,
        ]);

        // Release rooms
        $phongIds = DatPhongItemHistory::where('dat_phong_id', $booking->id)
            ->pluck('phong_id')->filter()->unique()->toArray();
        
        if (!empty($phongIds)) {
            Phong::whereIn('id', $phongIds)->update([
                'trang_thai' => 'trong',
                'don_dep' => true,
            ]);
        }

        // Send notifications
        $hoaDon = HoaDon::where('dat_phong_id', $booking->id)->latest()->first();
        if ($hoaDon) {
            $notificationService = new PaymentNotificationService();
            $notificationService->sendCheckoutNotification($booking, $hoaDon->id);
            $this->sendInvoiceEmail($booking, $hoaDon);
        }
    }
}
