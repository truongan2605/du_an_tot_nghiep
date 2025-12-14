<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatPhongItem;
use App\Models\DatPhong;
use App\Models\Phong;
use App\Models\GiuPhong;

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
        $availableRooms = Phong::whereDoesntHave('giuPhong', function($q) use ($checkIn, $checkOut) {
            $q->where('released', false)
              ->where('created_at', '<', $checkOut)
              ->where('het_han_luc', '>', $checkIn);
        })->get();

        // Nhóm theo loại phòng
        $groupedRooms = $availableRooms->groupBy('loai_phong_id');

        return view('admin.dat-phong.change-room', [
            'item'          => $item,
            'booking'       => $booking,
            'availableRooms'=> $availableRooms,
            'groupedRooms'  => $groupedRooms,
        ]);
    }


    // ============================
    // AJAX TÍNH GIÁ
    // ============================
public function calculate(Request $request, $id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;
    $room = Phong::findOrFail($request->room_id);

    $soDem = (int)$item->so_dem;

    // 1) PHỤ THU
    $adultExtra = (int)$item->number_adult;
    $childExtra = (int)$item->number_child;
    $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);

    // 2) VOUCHER
    $roomCount = $booking->items->count() ?: 1;
    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    // 3) TÍNH NGƯỢC GIÁ PHÒNG GỐC TỪ gia_tren_dem
    // gia_tren_dem = (Giá phòng + Phụ thu - Voucher) / Số đêm
    // => Giá phòng = (gia_tren_dem × Số đêm) - Phụ thu + Voucher
    $oldRoomPrice = ($item->gia_tren_dem * $soDem) - $extraFee + $voucherItem;
    $newRoomPrice = (float)$room->tong_gia * $soDem;

    // 4) CHÊNH LỆCH CHỈ GIÁ PHÒNG
    $diffRoomOnly = $newRoomPrice - $oldRoomPrice;

    // 5) BOOKING MỚI
    $bookingNew = $booking->tong_tien + $diffRoomOnly;

    return response()->json([
        'room_name' => $room->name,
        'new_total_format' => number_format($newRoomPrice + $extraFee).'đ',
        'voucher_amount' => $voucherItem,
        'voucher_amount_format' => number_format($voucherItem).'đ',
        'total_diff' => $diffRoomOnly,
        'total_diff_format' => number_format($diffRoomOnly).'đ',
        'booking_new_total_after_voucher_format' => number_format($bookingNew).'đ',
    ]);
}

public function change(Request $request, $id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;
    $room = Phong::findOrFail($request->new_room_id);

    $soDem = (int)$item->so_dem;

    // ✅ TÍNH LẠI PHỤ THU THEO SỨC CHỨA PHÒNG MỚI
    
    // Tổng số khách thực tế
    $totalGuests = $item->so_nguoi_o ?? 0;
    if ($totalGuests == 0) {
        $oldRoomCapacity = $item->phong->suc_chua ?? 2;
        $totalGuests = $oldRoomCapacity + ($item->number_adult ?? 0) + ($item->number_child ?? 0);
    }
    
    // Sức chứa phòng mới
    $newRoomCapacity = $room->suc_chua ?? 2;
    
    // Số khách vượt quá phòng mới
    $extraGuestsInNewRoom = max(0, $totalGuests - $newRoomCapacity);
    
    // Tỷ lệ người lớn/trẻ em từ phòng cũ
    $oldExtraAdults = $item->number_adult ?? 0;
    $oldExtraChildren = $item->number_child ?? 0;
    $oldTotalExtra = $oldExtraAdults + $oldExtraChildren;
    
    // Phân bổ lại
    if ($extraGuestsInNewRoom > 0 && $oldTotalExtra > 0) {
        $adultRatio = $oldExtraAdults / $oldTotalExtra;
        $newExtraAdults = round($extraGuestsInNewRoom * $adultRatio);
        $newExtraChildren = $extraGuestsInNewRoom - $newExtraAdults;
    } else {
        $newExtraAdults = 0;
        $newExtraChildren = 0;
    }
    
    $newExtraFee = ($newExtraAdults * 150000) + ($newExtraChildren * 60000);
    $oldExtraFee = ($oldExtraAdults * 150000) + ($oldExtraChildren * 60000);
    
    // Voucher
    $roomCount = $booking->items->count() ?: 1;
    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    // GIÁ PHÒNG CŨ - MỚI
    $oldRoomPrice = ($item->gia_tren_dem * $soDem) - $oldExtraFee + $voucherItem;
    $newRoomPrice = (float)$room->tong_gia * $soDem;

    // CHÊNH LỆCH GIÁ PHÒNG + CHÊNH LỆCH PHỤ THU
    $diffRoomOnly = $newRoomPrice - $oldRoomPrice;
    $diffExtraFee = $newExtraFee - $oldExtraFee;
    $totalDiff = $diffRoomOnly + $diffExtraFee;

    // UPDATE BOOKING
    $booking->tong_tien = (float)$booking->tong_tien + $totalDiff;
    $booking->save();

    // UPDATE ITEM - Lưu lại phụ thu mới
    $newGiaTrenDem = ($newRoomPrice + $newExtraFee - $voucherItem) / $soDem;
    
    $item->phong_id = $room->id;
    $item->loai_phong_id = $room->loai_phong_id;
    $item->gia_tren_dem = $newGiaTrenDem;
    $item->tong_item = $newRoomPrice;
    $item->number_adult = $newExtraAdults; // ✅ Cập nhật phụ thu mới
    $item->number_child = $newExtraChildren; // ✅ Cập nhật phụ thu mới
    $item->so_nguoi_o = $totalGuests; // ✅ Lưu tổng số khách
    $item->save();

    

    return back()->with('success', 'Đổi phòng thành công!');
}


/**
 * API lấy phòng trống cho admin
 */
public function getAvailableRooms(Request $request, $id)
{
    try {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        
        $oldRoomId = $request->get('old_room_id');
        if ($oldRoomId) {
            $oldRoomId = (int) $oldRoomId;
        }

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
        
        // Tính voucher
        $voucherPerRoom = 0;
        if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
            $voucherPerRoom = (float)$booking->discount_amount / $totalRooms;
        } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
            $voucherPerRoom = (float)$booking->voucher_discount / $totalRooms;
        }
        
        // Tính ngược giá phòng gốc
        $extraFee = ($currentItem->number_adult * 150000) + ($currentItem->number_child * 60000);
        $currentRoomPrice = ($currentItem->gia_tren_dem * $nights) - $extraFee + $voucherPerRoom;
        $currentPricePerNight = $nights > 0 ? $currentRoomPrice / $nights : 0;

        // Lấy ngày check-in/out
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
    ->whereNotIn('dat_phong.trang_thai', ['da_xac_nhan','dang_cho_xac_nhan','dang_su_dung'])
    ->where('dat_phong.id', '!=', $booking->id)
    ->whereRaw(
        "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? 
         AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
        [$toEndStr, $fromStartStr]
    )
    ->pluck('dat_phong_item.phong_id')
    ->toArray();


        // Phòng trống
        // 3️⃣ Phòng trống + không trùng ngày + khác phòng hiện tại
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
    $bookedRoomIds,          // phòng đã bị booking khác chiếm
    $currentBookingRoomIds   // phòng đã gán trong booking hiện tại
));

        // Load chi tiết
  $availableRooms = Phong::whereNotIn('id', $excludeRoomIds)
    ->whereIn('trang_thai', ['dang_o', 'trong']) // ❗ CHỈ PHÒNG ĐƯỢC PHÉP
    ->with(['loaiPhong', 'images'])
    ->get()
    ->map(function ($room) use ($currentPricePerNight, $currentItem, $nights) {
        $roomBasePrice = $room->tong_gia ?? 0;
        $roomCapacity = $room->suc_chua ?? 2;

        // ✅ TÍNH LẠI PHỤ THU THEO SỨC CHỨA PHÒNG MỚI
        
        // Tổng số khách thực tế trong phòng cũ
        $totalGuestsInOldRoom = $currentItem->so_nguoi_o ?? 0;
        
        // Nếu không có so_nguoi_o, tính từ phụ thu cũ
        if ($totalGuestsInOldRoom == 0) {
            $oldRoomCapacity = $currentItem->phong->suc_chua ?? 2;
            $totalGuestsInOldRoom = $oldRoomCapacity + ($currentItem->number_adult ?? 0) + ($currentItem->number_child ?? 0);
        }
        
        // Tính số khách vượt quá sức chứa phòng MỚI
        $extraGuestsInNewRoom = max(0, $totalGuestsInOldRoom - $roomCapacity);
        
        // Tỷ lệ người lớn/trẻ em từ phòng cũ
        $oldExtraAdults = $currentItem->number_adult ?? 0;
        $oldExtraChildren = $currentItem->number_child ?? 0;
        $oldTotalExtra = $oldExtraAdults + $oldExtraChildren;
        
        // Phân bổ lại người lớn/trẻ em theo tỷ lệ cũ
        if ($extraGuestsInNewRoom > 0 && $oldTotalExtra > 0) {
            $adultRatio = $oldExtraAdults / $oldTotalExtra;
            $extraAdults = round($extraGuestsInNewRoom * $adultRatio);
            $extraChildren = $extraGuestsInNewRoom - $extraAdults;
        } else {
            // Nếu không vượt, không có phụ thu
            $extraAdults = 0;
            $extraChildren = 0;
        }
        
        $extraCharge = ($extraAdults * 150000) + ($extraChildren * 60000);


                $roomTotalForStay = ($roomBasePrice + $extraCharge) * $nights;
                $priceDiff = $roomBasePrice - $currentPricePerNight;

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
                    'price_total' => $roomTotalForStay,
                    'extra_charge' => $extraCharge,
                    'extra_adults' => $extraAdults,
                    'extra_children' => $extraChildren,
                    'price_difference' => $priceDiff,
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
                'price' => $currentPricePerNight,
            ],
            'available_rooms' => $availableRooms,
            'booking_info' => [
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
                'nights' => $nights,
                'total_rooms' => $totalRooms,
                'voucher_per_room' => $voucherPerRoom,
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


}
