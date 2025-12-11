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

    // 👉 GROUP ROOM THEO LOẠI PHÒNG
    $groupedRooms = $availableRooms->groupBy('loai_phong_id');

    return view('admin.dat-phong.change-room', [
        'item' => $item,
        'booking' => $booking,
        'availableRooms' => $availableRooms,
        'groupedRooms' => $groupedRooms, // 👈 TRUYỀN XUỐNG BLADE
    ]);
}



    // ============================
    // AJAX TÍNH GIÁ KHI CLICK PHÒNG
    // ============================
public function calculate(Request $request, $id)
{
    $item = DatPhongItem::findOrFail($id);
    $booking = $item->datPhong;
    $room = Phong::findOrFail($request->room_id);

    $soDem = $item->so_dem;

    // Giá cũ & mới
    $oldRoomPrice = $item->gia_tren_dem * $soDem;
    $newRoomPrice = $room->tong_gia * $soDem;

    // Voucher chia đều mỗi phòng (DÙNG discount_amount — tiền)
    $voucherItem = 0.0;
    $roomCount = $booking->items->count() ?: 1;
    // Prioritize discount_amount (tiền). If not, fallback to voucher_discount if it's also a fixed amount in your system.
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float) $booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        // fallback (in case your DB sometimes stores money in voucher_discount)
        $voucherItem = (float) $booking->voucher_discount / $roomCount;
    }

    // Phụ thu: lấy từ item.tong_item - oldRoomPrice (giữ nguyên)
    $extraFee = (float)$item->tong_item - $oldRoomPrice;
    if ($extraFee < 0) $extraFee = 0.0;

    // Tổng giá trước/sau voucher
    $payableOld = max(0, ($oldRoomPrice + $extraFee) - $voucherItem);
    $payableNew = max(0, ($newRoomPrice + $extraFee) - $voucherItem);

    $diff = $payableNew - $payableOld;
    $bookingNewTotal = $booking->tong_tien + $diff;

    return response()->json([
        'room_name' => $room->name,
        'new_total_format' => number_format($newRoomPrice + $extraFee).'đ',
        'payable_old_format' => number_format($payableOld).'đ',
        'payable_new_format' => number_format($payableNew).'đ',
        // numeric voucher (số) và format (chuỗi) — JS sẽ dùng numeric để tính
        'voucher_amount' => $voucherItem,
        'voucher_amount_format' => number_format($voucherItem).'đ',
        'diff_format' => number_format($diff).'đ',
        'total_diff' => $diff,
        'booking_new_total_after_voucher_format' => number_format($bookingNewTotal).'đ',
    ]);
}



    // ============================
    // ÁP DỤNG ĐỔI PHÒNG
    // ============================
    public function change(Request $request, $id)
    {
        $item = DatPhongItem::findOrFail($id);
        $booking = $item->datPhong;
        $room = Phong::findOrFail($request->new_room_id);

        $soDem = $item->so_dem;

        // Giá phòng mới
        $newRoomPrice = $room->tong_gia * $soDem;

        // Tính phụ thu mới
        $totalAdult = $item->number_adult;
        $totalChild = $item->number_child;
        $capacity   = $room->suc_chua;

        $overAdult = max(0, $totalAdult - $capacity);
        $overChild = 0;
        if ($overAdult == 0) {
            $remain = $capacity - $totalAdult;
            $overChild = max(0, $totalChild - $remain);
        }

        $extraFee = $overAdult * 150000 + $overChild * 60000;

        // Voucher chia đều
        $voucherItem = 0;
        $roomCount = $booking->items->count();
        if ($roomCount > 0 && $booking->discount_amount > 0) {
            $voucherItem = $booking->discount_amount / $roomCount;
        }

        // Tổng mới
        $payableNew = max(0, ($newRoomPrice + $extraFee) - $voucherItem);

        // Tổng cũ
        $oldRoomPrice = $item->gia_tren_dem * $soDem;
        $payableOld = max(0, ($oldRoomPrice + $extraFee) - $voucherItem);

        $diff = $payableNew - $payableOld;

        // Update tổng booking
        $booking->tong_tien += $diff;
        $booking->save();

        // Lưu phòng
        $item->phong_id = $room->id;
        $item->loai_phong_id = $room->loai_phong_id;
        $item->gia_tren_dem = $room->tong_gia;
        $item->tong_item = $newRoomPrice + $extraFee;
        $item->save();

        return back()->with('success', 'Đổi phòng thành công!');
    }
}
