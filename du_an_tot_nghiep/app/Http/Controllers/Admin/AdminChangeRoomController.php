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
    // ======================================
    // FORM HIỂN THỊ DANH SÁCH PHÒNG TRỐNG
    // ======================================
    public function form($id)
    {
        $item = DatPhongItem::findOrFail($id);
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

        return view('admin.dat-phong.change-room', [
            'item' => $item,
            'booking' => $booking,
            'availableRooms' => $availableRooms
        ]);
    }


    // ======================================
    // AJAX TÍNH GIÁ (KHÔNG TRỪ VOUCHER LẦN 2)
    // ======================================
    public function calculate(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room = Phong::findOrFail($request->room_id);

        $soDem = (int)$item->so_dem;

        // Tổng giá cũ và mới
        $oldTotal = $item->gia_tren_dem * $soDem;
        $newTotal = $room->tong_gia * $soDem;

        // Voucher chỉ HIỂN THỊ – KHÔNG tác động vào diff
        $voucher = (float) ($booking->voucher_discount ?? 0);

        // Chênh lệch đúng (không trừ voucher)
        $diff = $newTotal - $oldTotal;

        // Tổng booking mới = tổng hiện tại + chênh lệch
        $bookingNew = $booking->tong_tien + $diff;

        return response()->json([
            'room_name' => $room->name,
            'new_total_format' => number_format($newTotal).'đ',
            'old_total_format' => number_format($oldTotal).'đ',
            'voucher_amount_format' => number_format($voucher).'đ',
            'total_diff_format' => number_format($diff).'đ',
            'total_diff' => $diff,
            'booking_new_total_format' => number_format($bookingNew).'đ',
        ]);
    }


    // ======================================
    // XỬ LÝ ĐỔI PHÒNG (KHÔNG TRỪ VOUCHER LẦN 2)
    // ======================================
    public function change(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $newRoom = Phong::findOrFail($request->new_room_id);

        $soDem = (int)$item->so_dem;

        // Giá cũ & mới
        $oldTotal = $item->gia_tren_dem * $soDem;
        $newTotal = $newRoom->tong_gia * $soDem;

        // Voucher chỉ để hiển thị — không dùng tính toán
        $voucher = (float) ($booking->voucher_discount ?? 0);

        // Chênh lệch chuẩn
        $diff = $newTotal - $oldTotal;

        // Cập nhật tổng booking
        $booking->tong_tien = $booking->tong_tien + $diff;
        $booking->save();

        // Cập nhật item
        $oldRoomID = $item->phong_id;

        $item->phong_id = $newRoom->id;
        $item->loai_phong_id = $newRoom->loai_phong_id;
        $item->gia_tren_dem = $newRoom->tong_gia;
        $item->tong_item = $newTotal;
        $item->save();

        // Cập nhật giữ phòng
        GiuPhong::where('dat_phong_id', $booking->id)
                ->where('phong_id', $oldRoomID)
                ->delete();

        GiuPhong::create([
            'dat_phong_id' => $booking->id,
            'phong_id' => $newRoom->id,
            'so_luong' => $item->so_luong,
            'het_han_luc' => $booking->ngay_tra_phong,
            'released' => false
        ]);

        return back()->with('success', 'Đổi phòng thành công!');
    }
}
