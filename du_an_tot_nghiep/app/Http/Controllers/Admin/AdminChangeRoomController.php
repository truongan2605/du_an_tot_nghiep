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
    // ============================
    // FORM HIỂN THỊ DANH SÁCH PHÒNG
    // ============================
    public function form($id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;

        $checkIn  = $booking->ngay_nhan_phong;
        $checkOut = $booking->ngay_tra_phong;

        if (!$checkIn || !$checkOut) {
            return back()->with('error', 'Booking thiếu ngày nhận/trả.');
        }

        // Lấy phòng trống theo giu_phong của bạn
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

    // ============================
    // AJAX TÍNH GIÁ
    // ============================
    public function calculate(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room = Phong::findOrFail($request->room_id);

        $soDem = $item->so_dem;

        // Giá cũ & mới
        $oldPrice = $item->gia_tren_dem;
        $newPrice = $room->tong_gia;

        $oldTotal = $oldPrice * $soDem;
        $newTotal = $newPrice * $soDem;

        // Voucher
        $voucherAmount = 0;

        if (!empty($booking->voucher_giam_phan_tram)) {
            $voucherAmount = round($oldTotal * $booking->voucher_giam_phan_tram / 100);
        } elseif (!empty($booking->voucher_giam_tien)) {
            $voucherAmount = min($booking->voucher_giam_tien, $oldTotal);
        }

        // Payable
        $payableOld = max(0, $oldTotal - $voucherAmount);
        $payableNew = max(0, $newTotal - $voucherAmount);

        // Chênh lệch
        $totalDiff = $payableNew - $payableOld;

        // Booking tổng mới
        $bookingNewTotal = max(0, $booking->tong_tien + max($totalDiff, 0));

        return response()->json([
            'room_name' => $room->name,
            'new_total_format' => number_format($newTotal) . 'đ',
            'total_diff_format' => number_format($totalDiff) . 'đ',

            'payable_old_format' => number_format($payableOld) . 'đ',
            'payable_new_format' => number_format($payableNew) . 'đ',

            'voucher_amount_format' => number_format($voucherAmount) . 'đ',

            'total_diff' => $totalDiff, // <---- BẮT BUỘC PHẢI CÓ

            'booking_new_total_after_voucher_format' => number_format($bookingNewTotal) . 'đ'
        ]);
    }

    // ============================
    // XỬ LÝ ĐỔI PHÒNG
    // ============================
    public function change(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;

        $newRoom = Phong::findOrFail($request->new_room_id);

        $soDem = $item->so_dem;

        // Cập nhật giá
        $oldPrice = $item->gia_tren_dem;
        $newPrice = $newRoom->tong_gia;

        $oldTotal = $oldPrice * $soDem;
        $newTotal = $newPrice * $soDem;

        // Voucher
        $voucherAmount = 0;
        if (!empty($booking->voucher_giam_phan_tram)) {
            $voucherAmount = round($oldTotal * $booking->voucher_giam_phan_tram / 100);
        } elseif (!empty($booking->voucher_giam_tien)) {
            $voucherAmount = min($booking->voucher_giam_tien, $oldTotal);
        }

        $payableOld = max(0, $oldTotal - $voucherAmount);
        $payableNew = max(0, $newTotal - $voucherAmount);

        // Chênh lệch
        $diff = $payableNew - $payableOld;

        if ($diff > 0) {
            $booking->tong_tien += $diff;
        }

        $booking->save();

        // Cập nhật item
        $oldRoomID = $item->phong_id;

        $item->phong_id = $newRoom->id;
        $item->loai_phong_id = $newRoom->loai_phong_id;
        $item->gia_tren_dem = $newRoom->tong_gia;
        $item->tong_item = $newTotal;
        $item->save();

        // Giữ phòng mới
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
