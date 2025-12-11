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

    // PHỤ THU
    $adultExtra = (int)$item->number_adult;
    $childExtra = (int)$item->number_child;
    $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);

    // VOUCHER
    $roomCount = $booking->items->count() ?: 1;
    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    // TÍNH NGƯỢC GIÁ PHÒNG GỐC
    $oldRoomPrice = ($item->gia_tren_dem * $soDem) - $extraFee + $voucherItem;
    $newRoomPrice = (float)$room->tong_gia * $soDem;

    // CHÊNH LỆCH
    $diffRoomOnly = $newRoomPrice - $oldRoomPrice;

    // UPDATE BOOKING
    $booking->tong_tien = (float)$booking->tong_tien + $diffRoomOnly;
    $booking->save();

    // UPDATE ITEM - Lưu lại gia_tren_dem mới (bao gồm phụ thu và voucher)
    $newGiaTrenDem = ($newRoomPrice + $extraFee - $voucherItem) / $soDem;
    
    $item->phong_id = $room->id;
    $item->loai_phong_id = $room->loai_phong_id;
    $item->gia_tren_dem = $newGiaTrenDem;
    $item->tong_item = $newRoomPrice;
    $item->save();

    return back()->with('success', 'Đổi phòng thành công!');
}




}
