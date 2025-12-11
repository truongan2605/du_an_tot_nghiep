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
        $item    = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room    = Phong::findOrFail($request->room_id);

        $soDem = (int)$item->so_dem;

        // 1) GIÁ PHÒNG CŨ – MỚI
        $oldRoomPrice = (float)$item->gia_tren_dem * $soDem;
        $newRoomPrice = (float)$room->tong_gia      * $soDem;

        // 2) PHỤ THU (lấy đúng theo DB)
        $adultExtra = (int)$item->number_adult; // số người lớn vượt
        $childExtra = (int)$item->number_child; // số trẻ vượt

        $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);

        // 3) VOUCHER CHIA THEO ITEM
        $roomCount = $booking->items->count() ?: 1;

        $voucherItem = 0;
        if ($booking->discount_amount > 0) {
            $voucherItem = $booking->discount_amount / $roomCount;
        } elseif ($booking->voucher_discount > 0) {
            $voucherItem = $booking->voucher_discount / $roomCount;
        }

        // 4) GIÁ CẦN TRẢ
        $payableOld = max(0, ($oldRoomPrice + $extraFee) - $voucherItem);
        $payableNew = max(0, ($newRoomPrice + $extraFee) - $voucherItem);

      // 5) CHÊNH LỆCH (chỉ tính chênh lệch giá phòng, không tính phụ thu)
$diffRoomOnly = $newRoomPrice - $oldRoomPrice;

// 6) BOOKING MỚI = Booking hiện tại + chênh lệch giá phòng
$bookingNew = $booking->tong_tien + $diffRoomOnly;

        return response()->json([
            'room_name' => $room->name,

            'new_total_format' => number_format($newRoomPrice + $extraFee).'đ',

            'payable_old_format' => number_format($payableOld).'đ',
            'payable_new_format' => number_format($payableNew).'đ',

            'voucher_amount'       => $voucherItem,
            'voucher_amount_format'=> number_format($voucherItem).'đ',

            'total_diff'      => $diffRoomOnly,
            'total_diff_format'=> number_format($diffRoomOnly).'đ',

            'booking_new_total_after_voucher_format' => number_format($bookingNew).'đ',
        ]);
    }


    // ============================
    // XỬ LÝ ĐỔI PHÒNG
    // ============================
    public function change(Request $request, $id)
    {
        $item    = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room    = Phong::findOrFail($request->new_room_id);

        $soDem = (int)$item->so_dem;

        // GIÁ PHÒNG MỚI
        $newRoomPrice = $room->tong_gia * $soDem;

        // PHỤ THU
        $adultExtra = (int)$item->number_adult;
        $childExtra = (int)$item->number_child;

        $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);

        // VOUCHER chia đều
        $voucherItem = 0;
        $roomCount = $booking->items->count() ?: 1;

        if ($booking->discount_amount > 0) {
            $voucherItem = $booking->discount_amount / $roomCount;
        } elseif ($booking->voucher_discount > 0) {
            $voucherItem = $booking->voucher_discount / $roomCount;
        }

     // GIÁ MỚI – GIÁ CŨ
$oldRoomPrice = $item->gia_tren_dem * $soDem;

// CHÊNH LỆCH CHỈ TÍNH GIÁ PHÒNG (không tính phụ thu vì không đổi)
$diffRoomOnly = $newRoomPrice - $oldRoomPrice;

// UPDATE BOOKING
$booking->tong_tien += $diffRoomOnly;
        $booking->save();

        // UPDATE ITEM
        $item->phong_id     = $room->id;
        $item->loai_phong_id= $room->loai_phong_id;
        $item->gia_tren_dem = $room->tong_gia;
        $item->tong_item    = $newRoomPrice + $extraFee;
        $item->save();

        return back()->with('success', 'Đổi phòng thành công!');
    }
}
