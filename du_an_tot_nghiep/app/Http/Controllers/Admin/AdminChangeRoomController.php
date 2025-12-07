<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatPhongItem;
use App\Models\DatPhong;
use App\Models\Phong;
use App\Models\GiuPhong;
use Carbon\Carbon;

class AdminChangeRoomController extends Controller
{
    // ===========================
    // FORM CHỌN PHÒNG MỚI
    // ===========================
  public function form($id)
{
    $item = DatPhongItem::findOrFail($id);

    // Lấy ngày nhận/trả từ booking cha
    $booking = $item->datPhong;
    $checkIn  = $booking->ngay_nhan_phong;
    $checkOut = $booking->ngay_tra_phong;

    if (!$checkIn || !$checkOut) {
        return back()->with('error', 'Booking thiếu ngày nhận/trả.');
    }

    // Tìm phòng trống dựa theo dữ liệu giu_phong của bạn
    $availableRooms = Phong::whereDoesntHave('giuPhong', function($q) use ($checkIn, $checkOut) {
        $q->where('released', false)
          ->where('created_at', '<', $checkOut)
          ->where('het_han_luc', '>', $checkIn);
    })->get();

    return view('admin.dat-phong.change-room', compact('item', 'availableRooms'));
}



    // ===========================
    // XỬ LÝ ĐỔI PHÒNG
    // ===========================
public function change(Request $request, $id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;

    $checkIn  = $booking->ngay_nhan_phong;
    $checkOut = $booking->ngay_tra_phong;

    $newRoomID = $request->new_room_id;
    $newRoom   = Phong::findOrFail($newRoomID);

    // ==========================
    //  GIÁ CŨ - GIÁ MỚI (THEO ĐÊM)
    // ==========================
    $oldPrice = $item->gia_tren_dem;               // giá/đêm phòng cũ
    $newPrice = $newRoom->tong_gia;                // giá/đêm phòng mới

    $soDem = $item->so_dem;

    // Chênh lệch tổng
    $totalDiff = ($newPrice - $oldPrice) * $soDem;

    // ==========================
    //  CẬP NHẬT TỔNG TIỀN BOOKING
    // ==========================
    if ($totalDiff > 0) {
        $booking->tong_tien += $totalDiff; // phòng mới đắt
    }

    // Áp dụng voucher %
    if ($booking->voucher_giam_phan_tram) {
        $booking->tong_tien -= ($booking->tong_tien * $booking->voucher_giam_phan_tram) / 100;
    }

    // Áp dụng voucher tiền
    if ($booking->voucher_giam_tien) {
        $booking->tong_tien -= $booking->voucher_giam_tien;
    }

    // Không âm
    $booking->tong_tien = max(0, $booking->tong_tien);
    $booking->save();

    // ==========================
    //  CẬP NHẬT DỮ LIỆU ITEM
    // ==========================
    $item->phong_id       = $newRoomID;
    $item->loai_phong_id  = $newRoom->loai_phong_id;   // <<< QUAN TRỌNG
    $item->gia_tren_dem   = $newPrice;                 // giá/đêm mới
    $item->tong_item      = $newPrice * $soDem;        // tổng tiền mới
    $item->save();

    // ==========================
    //  CẬP NHẬT GIỮ PHÒNG
    // ==========================
   // ==========================
//  XÓA GIỮ PHÒNG CŨ 
// ==========================
GiuPhong::where('dat_phong_id', $booking->id)
        ->where('phong_id', $item->getOriginal('phong_id')) // phòng cũ
        ->delete();


    // Tạo giữ phòng mới
  GiuPhong::create([
    'dat_phong_id' => $booking->id,
    'phong_id'     => $newRoomID,
    'so_luong'     => $item->so_luong,
    'het_han_luc'  => $checkOut,
    'released'     => false,
]);

    return back()->with('success', 'Đổi phòng thành công!');
}

public function calculate(Request $request, $id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;
    $room = Phong::findOrFail($request->room_id);

    // === GIÁ ĐÚNG ===
    $oldPrice = $item->gia_tren_dem;
    $newPrice = $room->tong_gia;  // <--- CHỈNH TẠI ĐÂY

    $soDem = $item->so_dem;

    $diffPerNight = $newPrice - $oldPrice;
    $totalDiff = $diffPerNight * $soDem;

    $bookingTotal = $booking->tong_tien + max($totalDiff, 0);

    $refund = $totalDiff < 0 ? abs($totalDiff) : 0;

    return response()->json([
        'room_name' => $room->name,
        'room_price_format' => number_format($newPrice).'đ/đêm',
        'diff_per_night_format' => number_format($diffPerNight).'đ',
        'total_diff_format'     => number_format($totalDiff).'đ',
        'new_total_format' => number_format($bookingTotal).'đ',
        'refund' => $refund,
        'refund_format' => number_format($refund).'đ'
    ]);
}



}
