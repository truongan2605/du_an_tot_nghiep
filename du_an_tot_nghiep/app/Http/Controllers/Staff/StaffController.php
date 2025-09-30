<?php

namespace App\Http\Controllers\Staff;

use App\Models\DatPhong;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
 
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