<?php

namespace App\Http\Controllers\Staff;

use App\Models\DatPhong;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
public function index()
    {
        $pendingBookings = DatPhong::where('trang_thai', 'dang_cho')->count();
        $todayCheckins = DatPhong::where('trang_thai', 'da_xac_nhan')
                                ->whereDate('ngay_nhan_phong', now()->toDateString())
                                ->count();
        $todayRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')
                              ->whereDate('ngay_nhan_phong', now()->toDateString())
                              ->sum('tong_tien');
        $events = DatPhong::where('trang_thai', '!=', 'da_huy')
                         ->get()
                         ->map(function ($booking) {
                             return [
                                 'title' => "Booking {$booking->ma_tham_chieu}",
                                 'start' => $booking->ngay_nhan_phong,
                                 'end' => $booking->ngay_tra_phong,
                             ];
                         });
        $recentActivities = DatPhong::where('trang_thai', '!=', 'dang_cho')
                                  ->orderBy('updated_at', 'desc')
                                  ->limit(5)
                                  ->get();

        return view('staff.index', compact('pendingBookings', 'todayCheckins', 'todayRevenue', 'events', 'recentActivities'));
    }
    public function bookings()
    {
        $bookings = DatPhong::where('trang_thai', 'dang_cho')->get();
        return view('staff.bookings', compact('bookings'));
    }

    public function confirm($id)
    {
        $booking = DatPhong::findOrFail($id);
        if ($booking->trang_thai !== 'dang_cho') {
            return redirect()->back()->with('error', 'Booking không thể xác nhận.');
        }

        $booking->trang_thai = 'da_xac_nhan';
        $booking->save();

        return redirect()->back()->with('success', 'Booking đã xác nhận.');
    }
}