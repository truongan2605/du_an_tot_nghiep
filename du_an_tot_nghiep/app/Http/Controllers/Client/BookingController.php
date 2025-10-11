<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Events\BookingCreated;
use App\Events\PaymentSuccess;
use App\Events\BookingCancelled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ngay_nhan_phong' => 'required|date|after:today',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'so_khach' => 'required|integer|min:1',
            'tong_tien' => 'required|numeric|min:0',
        ]);

        // Create booking
        $booking = DatPhong::create([
            'ma_tham_chieu' => 'BK' . Str::random(8),
            'nguoi_dung_id' => Auth::id(),
            'trang_thai' => 'dang_cho',
            'ngay_nhan_phong' => $request->ngay_nhan_phong,
            'ngay_tra_phong' => $request->ngay_tra_phong,
            'so_khach' => $request->so_khach,
            'tong_tien' => $request->tong_tien,
            'created_by' => Auth::id(),
            'phuong_thuc' => $request->phuong_thuc ?? 'tien_mat',
        ]);

        // Dispatch booking created event
        event(new BookingCreated($booking));

        return redirect()->route('booking.show', $booking)
            ->with('success', 'Đặt phòng thành công!');
    }

    public function confirmPayment(Request $request, DatPhong $booking)
    {
        // Create transaction record
        $transaction = GiaoDich::create([
            'dat_phong_id' => $booking->id,
            'so_tien' => $booking->tong_tien,
            'phuong_thuc' => $request->phuong_thuc,
            'trang_thai' => 'thanh_cong',
            'mo_ta' => 'Thanh toán đặt phòng',
        ]);

        // Update booking status
        $booking->update([
            'trang_thai' => 'da_xac_nhan',
            'can_thanh_toan' => false,
        ]);

        // Dispatch payment success event
        event(new PaymentSuccess($booking, $transaction));

        return redirect()->route('booking.show', $booking)
            ->with('success', 'Thanh toán thành công!');
    }

    public function cancel(DatPhong $booking)
    {
        // Update booking status
        $booking->update([
            'trang_thai' => 'da_huy',
        ]);

        // Dispatch booking cancelled event
        event(new BookingCancelled($booking));

        return redirect()->route('booking.index')
            ->with('success', 'Hủy đặt phòng thành công!');
    }
}

