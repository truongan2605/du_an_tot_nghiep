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
use Illuminate\Support\Facades\Storage;
use App\Services\PaymentNotificationService;

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
        foreach ($booking->datPhongItems as $item) {
            $unitPrice = $item->gia_tren_dem ?? 0;
            $qty = $item->so_luong ?? 1;
            $lineTotal = $unitPrice * $qty * $nights;
            $roomLines[] = [
                'phong_id'   => $item->phong_id,
                'ma_phong'   => $item->phong?->ma_phong ?? null,
                'loai'       => $item->loaiPhong?->ten ?? null,
                'unit_price' => $unitPrice,
                'qty'        => $qty,
                'nights'     => $nights,
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

    /**
     * Xóa ảnh CCCD từ snapshot_meta của booking
     */
    private function deleteCCCDImages(DatPhong $booking)
    {
        $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true) ?? [];

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
                $targetHoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $targetHoaDon->id)->sum('amount');
                $targetHoaDon->trang_thai = $markPaid ? 'da_thanh_toan' : 'da_xuat';
                $targetHoaDon->save();

                // free rooms & delete dat_phong_item
                $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                }

                DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->delete();

                $booking->checkout_at = now();
                $booking->checkout_by = Auth::id();
                $booking->trang_thai = 'hoan_thanh';
                $booking->blocks_checkin = false;
                $booking->is_late_checkout = true;
                $booking->late_checkout_fee_amount = $lateFee > 0 ? $lateFee : 0;
                $booking->save();

                DB::commit();

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

                $hoaDon->tong_thuc_thu = (float) HoaDonItem::where('hoa_don_id', $hoaDon->id)->sum('amount');
                $hoaDon->save();

                if ($markPaid) {
                    $phongIds = collect($datPhongItems)->pluck('phong_id')->filter()->unique()->toArray();
                    if (!empty($phongIds)) {
                        Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'trong', 'don_dep' => true, 'updated_at' => now()]);
                    }

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

            return redirect()->route('staff.bookings.show', $booking->id)
                ->with('success', 'Hoá đơn #' . $hoaDon->id . ' đã được đánh dấu là đã thanh toán và checkout hoàn tất.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Không thể xác nhận thanh toán: ' . $e->getMessage()]);
        }
    }
}
