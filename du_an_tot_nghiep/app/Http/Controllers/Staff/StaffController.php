<?php

namespace App\Http\Controllers\Staff;

use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiuPhong;
use App\Models\PhongDaDat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $availableRooms = Phong::where('trang_thai', 'trong')->with(['tang', 'loaiPhong'])->get();
        return view('staff.bookings', compact('bookings', 'availableRooms'));
    }

    public function confirm(Request $request, $id)
    {
        $request->validate(['phong_id' => 'nullable|exists:phong,id']);

        $booking = DatPhong::findOrFail($id);
        if ($booking->trang_thai !== 'dang_cho') {
            return redirect()->back()->with('error', 'Booking không thể xác nhận.');
        }

        DB::transaction(function () use ($booking, $request) {
            $booking->trang_thai = 'da_xac_nhan';
            $booking->save();

            if ($request->filled('phong_id')) {
                $phong_id = $request->phong_id;
                if ($this->checkAvailability($phong_id, $booking->ngay_nhan_phong, $booking->ngay_tra_phong)) {
                    PhongDaDat::create([
                        'dat_phong_item_id' => $booking->dat_phong_item_id, // Giả sử bạn có dat_phong_item_id, điều chỉnh nếu cần
                        'phong_id' => $phong_id,
                        'trang_thai' => 'da_dat',
                        'checkin_datetime' => $booking->ngay_nhan_phong,
                        'checkout_datetime' => $booking->ngay_tra_phong,
                    ]);
                    Phong::find($phong_id)->update(['trang_thai' => 'da_dat']);
                } else {
                    throw new \Exception('Phòng không khả dụng.');
                }
            }
        });

        return redirect()->back()->with('success', 'Booking đã được xác nhận.');
    }

    protected function checkAvailability($phong_id, $start, $end)
    {
        $overlappingBookings = PhongDaDat::where('phong_id', $phong_id)
                                        ->where('trang_thai', '!=', 'da_huy')
                                        ->where('checkin_datetime', '<', $end)
                                        ->where('checkout_datetime', '>', $start)
                                        ->count();
        $holds = GiuPhong::where('phong_id', $phong_id)
                         ->where('het_han_luc', '>', now())
                         ->count();
        return ($overlappingBookings + $holds) == 0;
    }

}