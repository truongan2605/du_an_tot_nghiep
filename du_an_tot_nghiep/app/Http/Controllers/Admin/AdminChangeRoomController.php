<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatPhongItem;
use App\Models\DatPhong;
use App\Models\Phong;
use App\Models\GiuPhong;
use App\Models\LichSuDoiPhong;


class AdminChangeRoomController extends Controller
{
    // ============================
    // FORM — DANH SÁCH PHÒNG TRỐNG
    // ============================
    public function form($id)
    {
        $item    = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;

        $checkIn  = $booking->ngay_nhan_phong;
        $checkOut = $booking->ngay_tra_phong;

        if (!$checkIn || !$checkOut) {
            return back()->with('error', 'Booking thiếu ngày nhận/trả.');
        }

        // Lọc phòng trống
        $availableRooms = Phong::whereDoesntHave('giuPhong', function ($q) use ($checkIn, $checkOut) {
            $q->where('released', false)
                ->where('created_at', '<', $checkOut)
                ->where('het_han_luc', '>', $checkIn);
        })->get();

        // Nhóm theo loại phòng
        $groupedRooms = $availableRooms->groupBy('loai_phong_id');

        return view('admin.dat-phong.change-room', [
            'item'          => $item,
            'booking'       => $booking,
            'availableRooms' => $availableRooms,
            'groupedRooms'  => $groupedRooms,
        ]);
    }


    // ============================
    // AJAX TÍNH GIÁ
    // ============================
public function calculate(Request $request, $id)
{
    $item = DatPhongItem::with('phong')->findOrFail($id);
    $booking = $item->datPhong;
    $room = Phong::findOrFail($request->room_id);

    $soDem = (int)$item->so_dem;

    // ===== PHÒNG CŨ =====
    $oldBase = $item->phong->tong_gia;
    $oldExtra = ($item->number_adult * 150000)
              + ($item->number_child * 60000);

    $oldTotalPerNight = $oldBase + $oldExtra;
    $oldTotal = $oldTotalPerNight * $soDem;

    // ===== PHÒNG MỚI =====
    $newBase = $room->tong_gia;

    // giữ nguyên tổng khách → tính phụ thu mới
    $totalGuests = $item->so_nguoi_o;
    $capacity = $room->suc_chua ?? 2;
    $extraGuests = max(0, $totalGuests - $capacity);

    $oldExtraTotal = $item->number_adult + $item->number_child;
    if ($extraGuests > 0 && $oldExtraTotal > 0) {
        $adultRatio = $item->number_adult / $oldExtraTotal;
        $newAdult = round($extraGuests * $adultRatio);
        $newChild = $extraGuests - $newAdult;
    } else {
        $newAdult = $newChild = 0;
    }

    $newExtra = ($newAdult * 150000) + ($newChild * 60000);
    $newTotalPerNight = $newBase + $newExtra;
    $newTotal = $newTotalPerNight * $soDem;

    // ===== CHÊNH LỆCH =====
    $diff = $newTotal - $oldTotal;
    $bookingAfter = $booking->tong_tien + $diff;

    return response()->json([
        'old_total' => $oldTotal,
        'new_total' => $newTotal,
        'diff' => $diff,
        'booking_after' => $bookingAfter,

        // format
        'old_total_f' => number_format($oldTotal).'đ',
        'new_total_f' => number_format($newTotal).'đ',
        'diff_f' => number_format($diff).'đ',
        'booking_after_f' => number_format($bookingAfter).'đ',
    ]);
}







   

public function change(Request $request, $id)
{
    $item    = DatPhongItem::with('phong')->findOrFail($id);
    $booking = $item->datPhong;
    $newRoom = Phong::findOrFail($request->new_room_id);
 // ✅ BẮT BUỘC PHẢI CÓ
    $oldPhongId = $item->phong_id;
    $soDem = (int) $item->so_dem;

    /* ===== PHÒNG CŨ ===== */
    $oldBasePrice = $item->phong->tong_gia;
    $oldExtraFee =
        ($item->number_adult * 150000) +
        ($item->number_child * 60000);

    $oldTotal = ($oldBasePrice + $oldExtraFee) * $soDem;

    /* ===== PHÒNG MỚI ===== */
    $totalGuests = $item->so_nguoi_o ?: (
        ($item->phong->suc_chua ?? 2) +
        ($item->number_adult ?? 0) +
        ($item->number_child ?? 0)
    );

    $extraGuests = max(0, $totalGuests - ($newRoom->suc_chua ?? 2));

    if ($extraGuests > 0) {
        $ratio = $item->number_adult / max(1, $item->number_adult + $item->number_child);
        $newExtraAdults = round($extraGuests * $ratio);
        $newExtraChildren = $extraGuests - $newExtraAdults;
    } else {
        $newExtraAdults = $newExtraChildren = 0;
    }

    $newExtraFee =
        ($newExtraAdults * 150000) +
        ($newExtraChildren * 60000);

    $newTotal = ($newRoom->tong_gia + $newExtraFee) * $soDem;

    /* ===== LOẠI ===== */
    $loai = $newTotal > $oldTotal ? 'nang_cap'
          : ($newTotal < $oldTotal ? 'ha_cap' : 'giu_nguyen');

    /* ===== UPDATE BOOKING ===== */
    $booking->tong_tien += ($newTotal - $oldTotal);
    $booking->save();

    /* ===== UPDATE ITEM ===== */
    $item->update([
        'phong_id' => $newRoom->id,
        'loai_phong_id' => $newRoom->loai_phong_id,
        'gia_tren_dem' => $newRoom->tong_gia + $newExtraFee,
        'tong_item' => $newTotal,
        'number_adult' => $newExtraAdults,
        'number_child' => $newExtraChildren,
        'so_nguoi_o' => $totalGuests,
    ]);
    /* =====================================================
 | 🆕 GIỮ ĐỒ ĂN – DỊCH VỤ – VẬT DỤNG
 ===================================================== */

// ✅ hoa_don_items
\DB::table('hoa_don_items')
    ->where('phong_id', $oldPhongId)
    ->whereIn('hoa_don_id', function ($query) use ($booking) {
        $query->select('id')
            ->from('hoa_don')
            ->where('dat_phong_id', $booking->id);
    })
    ->update([
        'phong_id' => $newRoom->id
    ]);

// ✅ phong_vat_dung_consumptions
\DB::table('phong_vat_dung_consumptions')
    ->where('dat_phong_id', $booking->id)
    ->where('phong_id', $oldPhongId)
    ->update([
        'phong_id' => $newRoom->id
    ]);

// ✅ vat_dung_incidents
\DB::table('vat_dung_incidents')
    ->where('dat_phong_id', $booking->id)
    ->where('phong_id', $oldPhongId)
    ->update([
        'phong_id' => $newRoom->id
    ]);


    /* ===== LỊCH SỬ ===== */
    LichSuDoiPhong::create([
        'dat_phong_id' => $booking->id,
        'dat_phong_item_id' => $item->id,
        'phong_cu_id' => $item->getOriginal('phong_id'),
        'phong_moi_id' => $newRoom->id,
        'gia_cu' => $oldTotal,
        'gia_moi' => $newTotal,
        'so_dem' => $soDem,
        'loai' => $loai,
        'nguoi_thuc_hien' => 'Admin',
    ]);

    return back()->with('success', 'Đổi phòng thành công');
}





    /**
     * API lấy phòng trống cho admin
     */
   public function getAvailableRooms(Request $request, $id)
{
    try {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;

        $currentItem = $item;
        $currentRoom = $currentItem->phong;

        if (!$currentRoom) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng'
            ], 404);
        }

        $nights = \Carbon\Carbon::parse($booking->ngay_nhan_phong)
            ->diffInDays(\Carbon\Carbon::parse($booking->ngay_tra_phong));

        $totalRooms = $booking->datPhongItems->count() ?: 1;

        // ✅ LẤY GIÁ GỐC PHÒNG HIỆN TẠI (QUAN TRỌNG)
        $currentRoomBasePrice = $currentRoom->tong_gia ?? 0;

        $checkIn = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
        $checkOut = \Carbon\Carbon::parse($booking->ngay_tra_phong);

        // Lấy tất cả phòng không bảo trì
        $allRooms = Phong::where('trang_thai', 'trong')
            ->pluck('id')
            ->toArray();

        // Lấy phòng đã đặt trong khoảng thời gian
        $fromStartStr = $checkIn->copy()->setTime(14, 0)->toDateTimeString();
        $toEndStr = $checkOut->copy()->setTime(12, 0)->toDateTimeString();

        $bookedRoomIds = \DB::table('dat_phong_item')
            ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
            ->whereNotNull('dat_phong_item.phong_id')
            ->whereNotIn('dat_phong.trang_thai', ['da_xac_nhan', 'dang_cho_xac_nhan', 'dang_su_dung'])
            ->where('dat_phong.id', '!=', $booking->id)
            ->whereRaw(
                "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? 
                 AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                [$toEndStr, $fromStartStr]
            )
            ->pluck('dat_phong_item.phong_id')
            ->toArray();

        // Phòng trống
        $availableRoomIds = array_diff(
            $allRooms,
            $bookedRoomIds,
            [$currentRoom->id]
        );

        $currentBookingRoomIds = $booking->datPhongItems()
            ->whereNotNull('phong_id')
            ->pluck('phong_id')
            ->toArray();

        $excludeRoomIds = array_unique(array_merge(
            $bookedRoomIds,
            $currentBookingRoomIds
        ));

        // Load chi tiết
        $availableRooms = Phong::whereNotIn('id', $excludeRoomIds)
            ->whereIn('trang_thai', ['dang_o', 'trong'])
            ->with(['loaiPhong', 'images'])
            ->get()
            ->map(function ($room) use ($currentRoomBasePrice, $currentItem, $nights) {
                // ✅ GIÁ GỐC PHÒNG MỚI
                $roomBasePrice = $room->tong_gia ?? 0;
                $roomCapacity = $room->suc_chua ?? 2;

                // ✅ TÍNH LẠI PHỤ THU THEO SỨC CHỨA PHÒNG MỚI
                $totalGuestsInOldRoom = $currentItem->so_nguoi_o ?? 0;

                if ($totalGuestsInOldRoom == 0) {
                    $oldRoomCapacity = $currentItem->phong->suc_chua ?? 2;
                    $totalGuestsInOldRoom = $oldRoomCapacity + ($currentItem->number_adult ?? 0) + ($currentItem->number_child ?? 0);
                }

                $extraGuestsInNewRoom = max(0, $totalGuestsInOldRoom - $roomCapacity);

                $oldExtraAdults = $currentItem->number_adult ?? 0;
                $oldExtraChildren = $currentItem->number_child ?? 0;
                $oldTotalExtra = $oldExtraAdults + $oldExtraChildren;

                if ($extraGuestsInNewRoom > 0 && $oldTotalExtra > 0) {
                    $adultRatio = $oldExtraAdults / $oldTotalExtra;
                    $extraAdults = round($extraGuestsInNewRoom * $adultRatio);
                    $extraChildren = $extraGuestsInNewRoom - $extraAdults;
                } else {
                    $extraAdults = 0;
                    $extraChildren = 0;
                }

                $extraCharge = ($extraAdults * 150000) + ($extraChildren * 60000);

                // ✅ TỔNG GIÁ PHÒNG MỚI (GIÁ GỐC + PHỤ THU) × SỐ ĐÊM
                $roomTotalForStay = ($roomBasePrice * $nights) + ($extraCharge * $nights);

                // ✅ SO SÁNH GIÁ GỐC VỚI GIÁ GỐC (KHÔNG BAO GỒM PHỤ THU)
                $priceDiff = $roomBasePrice - $currentRoomBasePrice;

                $imagePath = '/images/room-placeholder.jpg';
                if ($room->images && $room->images->count() > 0) {
                    $firstImage = $room->images->first();
                    if ($firstImage->image_url) {
                        $imagePath = $firstImage->image_url;
                    } elseif ($firstImage->image_path) {
                        $imagePath = asset('storage/' . $firstImage->image_path);
                    }
                }

                return [
                    'id' => $room->id,
                    'code' => $room->ma_phong,
                    'name' => $room->name,
                    'type_name' => $room->loaiPhong->ten ?? 'Standard',
                    'type_id' => $room->loai_phong_id,
                    'price_per_night' => $roomBasePrice, // ✅ Giá gốc/đêm
                    'price_total' => $roomTotalForStay, // ✅ Tổng tiền (giá + phụ thu) × đêm
                    'extra_charge' => $extraCharge, // ✅ Phụ thu/đêm
                    'extra_adults' => $extraAdults,
                    'extra_children' => $extraChildren,
                    'price_difference' => $priceDiff, // ✅ Chênh lệch GIÁ GỐC
                    'image' => $imagePath,
                    'capacity' => $roomCapacity,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'current_room' => [
                'id' => $currentRoom->id,
                'code' => $currentRoom->ma_phong,
                'name' => $currentRoom->name,
                'price' => $currentRoomBasePrice, // ✅ Giá gốc phòng hiện tại
            ],
            'available_rooms' => $availableRooms,
            'booking_info' => [
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'nights' => $nights,
                'total_rooms' => $totalRooms,
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('❌ API Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
 * Form đổi phòng lỗi (không tính tiền)
 */
public function formError($id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;

    if (!$booking->checked_in_at) {
        return back()->with('error', 'Chỉ có thể đổi phòng lỗi khi đã check-in!');
    }

    // ✅ TÍNH PHỤ THU PHÒNG HIỆN TẠI (giống calculate)
    $currentRoomBase = $item->phong->tong_gia ?? 0;
    $currentExtraFee = ($item->number_adult * 150000) + ($item->number_child * 60000);
    $currentTotalPerNight = $currentRoomBase + $currentExtraFee;
    
    $nights = \Carbon\Carbon::parse($booking->ngay_nhan_phong)
        ->diffInDays(\Carbon\Carbon::parse($booking->ngay_tra_phong));

    return view('admin.dat-phong.change-room-error', [
        'item' => $item,
        'booking' => $booking,
        'currentRoomBase' => $currentRoomBase,
        'currentExtraFee' => $currentExtraFee,
        'currentTotalPerNight' => $currentTotalPerNight,
        'nights' => $nights,
    ]);
}

/**
 * API lấy phòng trống cho đổi phòng lỗi
 */
 public function getAvailableRoomsForError(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $currentRoom = $item->phong;

        if (!$booking->checked_in_at) {
            return response()->json(['success' => false, 'message' => 'Chưa check-in'], 403);
        }

        $checkIn = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
        $checkOut = \Carbon\Carbon::parse($booking->ngay_tra_phong);
        $nights = $checkIn->diffInDays($checkOut);

        $allRooms = Phong::where('trang_thai', 'trong')->pluck('id')->toArray();

        $fromStartStr = $checkIn->copy()->setTime(14, 0)->toDateTimeString();
        $toEndStr = $checkOut->copy()->setTime(12, 0)->toDateTimeString();

        $bookedRoomIds = \DB::table('dat_phong_item')
            ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
            ->whereNotNull('dat_phong_item.phong_id')
            ->whereNotIn('dat_phong.trang_thai', ['da_xac_nhan', 'dang_cho_xac_nhan', 'dang_su_dung'])
            ->where('dat_phong.id', '!=', $booking->id)
            ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                [$toEndStr, $fromStartStr])
            ->pluck('dat_phong_item.phong_id')
            ->toArray();

        $currentBookingRoomIds = $booking->datPhongItems()
            ->whereNotNull('phong_id')
            ->pluck('phong_id')
            ->toArray();

        $excludeRoomIds = array_unique(array_merge($bookedRoomIds, $currentBookingRoomIds));
        
        // ✅ GIÁ PHÒNG HIỆN TẠI (giống calculate)
        $currentRoomBasePrice = $currentRoom->tong_gia ?? 0;
        $currentExtraFee = ($item->number_adult * 150000) + ($item->number_child * 60000);
        $currentRoomTotalPrice = $currentRoomBasePrice + $currentExtraFee; // Tổng/đêm (có phụ thu)
        
        $showLowerPrice = $request->get('show_lower_price', false);

        $availableRooms = Phong::whereNotIn('id', $excludeRoomIds)
            ->whereIn('trang_thai', ['dang_o', 'trong'])
            ->with(['loaiPhong', 'images'])
            ->get()
            ->map(function ($room) use ($currentRoomTotalPrice, $nights, $item) {
                $roomBasePrice = $room->tong_gia ?? 0;
                $roomCapacity = $room->suc_chua ?? 2;

                $totalGuests = $item->so_nguoi_o ?? 0;
                if ($totalGuests == 0) {
                    $oldRoomCapacity = $item->phong->suc_chua ?? 2;
                    $totalGuests = $oldRoomCapacity + ($item->number_adult ?? 0) + ($item->number_child ?? 0);
                }

                $extraGuestsInNewRoom = max(0, $totalGuests - $roomCapacity);
                $oldExtraAdults = $item->number_adult ?? 0;
                $oldExtraChildren = $item->number_child ?? 0;
                $oldTotalExtra = $oldExtraAdults + $oldExtraChildren;

                if ($extraGuestsInNewRoom > 0 && $oldTotalExtra > 0) {
                    $adultRatio = $oldExtraAdults / $oldTotalExtra;
                    $extraAdults = round($extraGuestsInNewRoom * $adultRatio);
                    $extraChildren = $extraGuestsInNewRoom - $extraAdults;
                } else {
                    $extraAdults = 0;
                    $extraChildren = 0;
                }

                $extraCharge = ($extraAdults * 150000) + ($extraChildren * 60000);
                
                // ✅ TỔNG GIÁ PHÒNG MỚI (GIÁ GỐC + PHỤ THU)
                $newRoomTotalPrice = $roomBasePrice + $extraCharge;
                
                // ✅ SO SÁNH VỚI GIÁ ĐÃ LƯU (đã có phụ thu)
                $priceDiff = $newRoomTotalPrice - $currentRoomTotalPrice;
                $isUpgrade = $priceDiff >= 0;

                $imagePath = '/images/room-placeholder.jpg';
                if ($room->images && $room->images->count() > 0) {
                    $firstImage = $room->images->first();
                    if ($firstImage->image_url) {
                        $imagePath = $firstImage->image_url;
                    } elseif ($firstImage->image_path) {
                        $imagePath = asset('storage/' . $firstImage->image_path);
                    }
                }

                return [
                    'id' => $room->id,
                    'code' => $room->ma_phong,
                    'name' => $room->name,
                    'type_name' => $room->loaiPhong->ten ?? 'Standard',
                    'type_id' => $room->loai_phong_id,
                    'price_per_night' => $roomBasePrice,
                    'extra_charge' => $extraCharge,
                    'extra_adults' => $extraAdults,
                    'extra_children' => $extraChildren,
                    'price_difference' => $priceDiff, // ✅ Chênh lệch đã bao gồm phụ thu
                    'is_upgrade' => $isUpgrade,
                    'is_downgrade' => $priceDiff < 0,
                    'image' => $imagePath,
                    'capacity' => $roomCapacity,
                ];
            })
            ->filter(function($room) use ($showLowerPrice) {
                if (!$showLowerPrice) {
                    return $room['is_upgrade'];
                }
                return true;
            })
            ->groupBy('type_id')
            ->map(function($rooms, $typeId) {
                return [
                    'type_id' => $typeId,
                    'type_name' => $rooms->first()['type_name'],
                    'rooms' => $rooms->values()->toArray()
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'current_room' => [
                'id' => $currentRoom->id,
                'code' => $currentRoom->ma_phong,
                'name' => $currentRoom->name,
                'price' => $currentRoomTotalPrice, // ✅ Giá đã bao gồm phụ thu
            ],
            'available_rooms' => $availableRooms,
            'booking_info' => [
                'nights' => $nights,
                'is_checked_in' => true,
            ],
            'showing_lower_price' => (bool)$showLowerPrice,
        ]);
    }

    public function changeError(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room = Phong::findOrFail($request->new_room_id);

        if (!$booking->checked_in_at) {
            return back()->with('error', 'Chỉ có thể đổi phòng lỗi khi đã check-in!');
        }

        $soDem = (int)$item->so_dem;
        $oldPhongId = $item->phong_id;

        /* ===== PHÒNG CŨ (giống calculate) ===== */
        $oldBase = $item->phong->tong_gia; // Giá gốc
        $oldExtra = ($item->number_adult * 150000) + ($item->number_child * 60000);
        $oldTotalPerNight = $oldBase + $oldExtra;
        $oldTotal = $oldTotalPerNight * $soDem;

        /* ===== PHÒNG MỚI (giống calculate) ===== */
        $newBase = $room->tong_gia ?? 0;
        
        $totalGuests = $item->so_nguoi_o;
        $capacity = $room->suc_chua ?? 2;
        $extraGuests = max(0, $totalGuests - $capacity);

        $oldExtraTotal = $item->number_adult + $item->number_child;
        if ($extraGuests > 0 && $oldExtraTotal > 0) {
            $adultRatio = $item->number_adult / $oldExtraTotal;
            $newExtraAdults = round($extraGuests * $adultRatio);
            $newExtraChildren = $extraGuests - $newExtraAdults;
        } else {
            $newExtraAdults = $newExtraChildren = 0;
        }

        $newExtra = ($newExtraAdults * 150000) + ($newExtraChildren * 60000);
        $newTotalPerNight = $newBase + $newExtra;
        $newTotal = $newTotalPerNight * $soDem;

        /* ===== CHÊNH LỆCH ===== */
        $priceDifference = $newTotal - $oldTotal;

        $loaiDoiPhong = '';
        $message = '';

        if ($priceDifference > 0) {
            $loaiDoiPhong = 'nang_cap';
            $message = "Đổi phòng lỗi thành công! (Nâng cấp miễn phí)";
        } elseif ($priceDifference < 0) {
            $loaiDoiPhong = 'ha_cap';
            $refundAmount = abs($priceDifference);
            $booking->tong_tien -= $refundAmount;
            $booking->save();
            $message = "Đổi phòng lỗi thành công! Đã hoàn lại " . number_format($refundAmount) . "đ cho khách.";
        } else {
            $loaiDoiPhong = 'giu_nguyen';
            $message = "Đổi phòng lỗi thành công! (Phòng ngang bằng)";
        }

        /* ===== UPDATE ITEM ===== */
        // ✅ TÍNH GIÁ TRÊN ĐÊM (bao gồm phụ thu, trừ voucher)
        $item->gia_tren_dem = $newBase + $newExtra; // ✅ ĐÚNG
        
        $item->phong_id = $room->id;
        $item->loai_phong_id = $room->loai_phong_id;
        $item->gia_tren_dem = $newBase; // ✅ Giá + phụ thu - voucher/đêm
        $item->tong_item = $newBase * $soDem; // ✅ Chỉ lưu giá phòng gốc
        $item->number_adult = $newExtraAdults;
        $item->number_child = $newExtraChildren;
        $item->so_nguoi_o = $totalGuests;
        $item->save();

        /* ===== GIỮ ĐỒ ĂN & DỊCH VỤ ===== */
        \DB::table('hoa_don_items')
            ->where('phong_id', $oldPhongId)
            ->whereIn('hoa_don_id', function($query) use ($booking) {
                $query->select('id')->from('hoa_don')->where('dat_phong_id', $booking->id);
            })
            ->update(['phong_id' => $room->id]);

        \DB::table('phong_vat_dung_consumptions')
            ->where('dat_phong_id', $booking->id)
            ->where('phong_id', $oldPhongId)
            ->update(['phong_id' => $room->id]);

        \DB::table('vat_dung_incidents')
            ->where('dat_phong_id', $booking->id)
            ->where('phong_id', $oldPhongId)
            ->update(['phong_id' => $room->id]);

        /* ===== LỊCH SỬ ===== */
        \App\Models\LichSuDoiPhong::create([
            'dat_phong_id' => $booking->id,
            'dat_phong_item_id' => $item->id,
            'phong_cu_id' => $oldPhongId,
            'phong_moi_id' => $room->id,
            'gia_cu' => $oldTotal, // ✅ Giá cũ (đã có phụ thu)
            'gia_moi' => $newTotal, // ✅ Giá mới (đã có phụ thu)
            'so_dem' => $soDem,
            'loai' => $loaiDoiPhong,
            'nguoi_thuc_hien' => 'admin',
        ]);

        return back()->with('success', $message);
    }



}
