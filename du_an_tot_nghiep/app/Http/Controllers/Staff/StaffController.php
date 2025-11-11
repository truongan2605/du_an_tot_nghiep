<?php

namespace App\Http\Controllers\Staff;

use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiuPhong;
use App\Models\LoaiPhong;
use App\Models\PhongDaDat;
use App\Models\DatPhongItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
    public function index()
    {
        $today = now();
        $weekRange = [$today->startOfWeek()->toDateString(), $today->endOfWeek()->toDateString()];
        $month = $today->month;
        $year = $today->year;


        $activeStatus = ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh'];
        $activeQuery = DatPhong::whereIn('trang_thai', $activeStatus)
            ->where('trang_thai', '!=', 'da_huy');

        $pendingBookings = DatPhong::where('trang_thai', 'dang_cho')->count();


        $todayCheckins = $activeQuery->clone()
            ->whereRaw('DATE(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$today->toDateString()])
            ->count();


        $todayCheckouts = DatPhong::whereIn('trang_thai', ['dang_o', 'da_gan_phong'])
            ->whereRaw('DATE(ngay_tra_phong) = ?', [$today->toDateString()])
            ->count();

        $todayRevenue = $activeQuery->clone()
            ->whereRaw('DATE(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$today->toDateString()])
            ->sum('tong_tien');


        $todayDeposit = $activeQuery->clone()
            ->whereRaw('DATE(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$today->toDateString()])
            ->sum(DB::raw('COALESCE(deposit_amount, 0)'));


        $weeklyRevenue = $activeQuery->clone()
            ->whereRaw('COALESCE(checked_in_at, ngay_nhan_phong) BETWEEN ? AND ?', $weekRange)
            ->sum('tong_tien');
        $weeklyDeposit = $activeQuery->clone()
            ->whereRaw('COALESCE(checked_in_at, ngay_nhan_phong) BETWEEN ? AND ?', $weekRange)
            ->sum(DB::raw('COALESCE(deposit_amount, 0)'));


        $monthlyRevenue = $activeQuery->clone()
            ->whereRaw('YEAR(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$year])
            ->whereRaw('MONTH(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$month])
            ->sum('tong_tien');
        $monthlyDeposit = $activeQuery->clone()
            ->whereRaw('YEAR(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$year])
            ->whereRaw('MONTH(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$month])
            ->sum(DB::raw('COALESCE(deposit_amount, 0)'));


        $totalRevenue = $activeQuery->clone()->sum('tong_tien');
        $totalDeposit = $activeQuery->clone()->sum(DB::raw('COALESCE(deposit_amount, 0)'));


        $availableRooms = Phong::where('trang_thai', 'trong')->count();


        $events = DatPhong::select('id', 'ma_tham_chieu', 'trang_thai', 'ngay_nhan_phong', 'ngay_tra_phong')
            ->whereIn('trang_thai', $activeStatus)
            ->with('user:id,name')
            ->get()
            ->map(fn($b) => [
                'title' => 'BK-' . $b->ma_tham_chieu,
                'start' => $b->ngay_nhan_phong,
                'end' => $b->ngay_tra_phong,
                'description' => "Khách: " . ($b->user->name ?? 'Ẩn danh') . " | " . ucfirst($b->trang_thai)
            ]);


        $recentActivities = DatPhong::whereIn('trang_thai', $activeStatus)
            ->orderByDesc('updated_at')->take(20)->get();

        $chartLabels = [];
        $checkinData = [];
        $checkoutData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $chartLabels[] = $today->copy()->subDays($i)->format('d/m');
            $checkinData[] = $activeQuery->clone()
                ->whereRaw('DATE(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$date])
                ->count();
            $checkoutData[] = DatPhong::whereIn('trang_thai', ['dang_o'])
                ->whereRaw('DATE(ngay_tra_phong) = ?', [$date])
                ->count();
        }


        Log::info('Index Stats - ' . $today->format('Y-m-d'), [
            'todayRevenue' => $todayRevenue,
            'todayDeposit' => $todayDeposit,
            'monthlyRevenue' => $monthlyRevenue,
            'activeBookingsCount' => $activeQuery->count()
        ]);

        return view('staff.index', compact(
            'pendingBookings',
            'todayCheckins',
            'todayCheckouts',
            'todayRevenue',
            'weeklyRevenue',
            'monthlyRevenue',
            'totalRevenue',
            'availableRooms',
            'events',
            'recentActivities',
            'chartLabels',
            'checkinData',
            'checkoutData',
            'todayDeposit',
            'weeklyDeposit',
            'monthlyDeposit',
            'totalDeposit'
        ));
    }

    public function reports()
    {
        $today = now();
        $month = $today->month;
        $year = $today->year;
        $startOfMonth = $today->copy()->startOfMonth();


        $activeStatus = ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh'];
        $activeQuery = DatPhong::whereIn('trang_thai', $activeStatus)
            ->where('trang_thai', '!=', 'da_huy');


        $monthlyRevenue = $activeQuery->clone()
            ->whereRaw('YEAR(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$year])
            ->whereRaw('MONTH(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$month])
            ->sum('tong_tien');


        $bookingsThisMonth = $activeQuery->clone()
            ->whereRaw('YEAR(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$year])
            ->whereRaw('MONTH(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$month])
            ->count();


        $availableRooms = Phong::where('trang_thai', 'trong')->count();


        $monthlyDeposit = $activeQuery->clone()
            ->whereRaw('YEAR(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$year])
            ->whereRaw('MONTH(COALESCE(checked_in_at, ngay_nhan_phong)) = ?', [$month])
            ->sum(DB::raw('COALESCE(deposit_amount, 0)'));


        $weeklyRevenue = [];
        $weeklyBookings = [];
        $weeklyDeposit = [];
        for ($i = 0; $i < 4; $i++) {
            $weekStart = $startOfMonth->copy()->addDays(7 * $i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekRange = [$weekStart->toDateString(), $weekEnd->toDateString()];

            $weeklyRevenue[] = $activeQuery->clone()
                ->whereRaw('COALESCE(checked_in_at, ngay_nhan_phong) BETWEEN ? AND ?', $weekRange)
                ->sum('tong_tien');

            $weeklyBookings[] = $activeQuery->clone()
                ->whereRaw('COALESCE(checked_in_at, ngay_nhan_phong) BETWEEN ? AND ?', $weekRange)
                ->count();

            $weeklyDeposit[] = $activeQuery->clone()
                ->whereRaw('COALESCE(checked_in_at, ngay_nhan_phong) BETWEEN ? AND ?', $weekRange)
                ->sum(DB::raw('COALESCE(deposit_amount, 0)'));
        }


        Log::info('Reports Stats - ' . $today->format('Y-m-d'), [
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyDeposit' => $monthlyDeposit,
            'weeklyRevenue' => $weeklyRevenue,
            'activeBookingsCount' => $activeQuery->count()
        ]);

        return view('staff.reports', compact(
            'monthlyRevenue',
            'bookingsThisMonth',
            'availableRooms',
            'weeklyRevenue',
            'weeklyBookings',
            'monthlyDeposit',
            'weeklyDeposit'
        ));
    }
    
    public function roomOverview()
    {
        $rooms = Phong::with(['tang', 'loaiPhong'])
            ->orderBy('tang_id')
            ->orderBy('ma_phong')
            ->get();
        $floors = $rooms->groupBy(fn($room) => $room->tang->so_tang ?? $room->tang->id);
        return view('staff.room-overview', compact('floors'));
    }

    public function checkinForm()
    {
        $bookings = DatPhong::with(['nguoiDung', 'giaoDichs' => function ($q) {
            $q->where('trang_thai', 'thanh_cong');
        }])
            ->whereIn('trang_thai', ['da_xac_nhan', 'da_gan_phong'])

            ->orderBy('ngay_nhan_phong', 'asc')
            ->get()
            ->map(function ($booking) {
                $paid      = $booking->giaoDichs->sum('so_tien');
                $remaining = $booking->tong_tien - $paid;

                $booking->paid       = $paid;
                $booking->remaining  = $remaining;
                $booking->can_checkin = $remaining <= 0;


                $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
                $today       = \Carbon\Carbon::today();

                $booking->checkin_status = $checkinDate->isToday()
                    ? 'Hôm nay'
                    : ($checkinDate->isFuture() ? 'Sắp tới' : 'Quá hạn');

                $booking->checkin_date_diff = $checkinDate->isFuture()
                    ? $checkinDate->diffInDays($today) . ' ngày nữa'
                    : ($checkinDate->isPast() ? 'Quá ' . $checkinDate->diffInDays($today) . ' ngày' : '');

                return $booking;
            });

        Log::info('Checkin bookings loaded (ALL)', [
            'count' => $bookings->count(),
            'ids'   => $bookings->pluck('id')->toArray(),
        ]);

        return view('staff.checkin', compact('bookings'));
    }

    public function processCheckin(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:dat_phong,id']);

        $booking = DatPhong::with(['datPhongItems', 'giaoDichs'])->findOrFail($request->booking_id);

        if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
            return redirect()->back()->with('error', 'Booking không thể check-in');
        }

        $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
        $remaining = $booking->tong_tien - $paid;

        if ($remaining > 0) {
            return redirect()->back()->with('error', "Cần thanh toán còn lại " . number_format($remaining) . " VND trước khi check-in.");
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'trang_thai' => 'dang_su_dung',
                'checked_in_at' => now(),
            ]);

            $phongIds = $booking->datPhongItems->pluck('phong_id')->filter()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_o']);
            }
        });

        return redirect()->route('staff.checkin')
            ->with('success', 'Check-in thành công cho booking ' . $booking->ma_tham_chieu . ' lúc ' . now()->format('H:i d/m/Y'));
    }
    public function checkoutForm()
    {
        $bookings = DatPhong::whereIn('trang_thai', ['da_gan_phong', 'dang_o'])
            ->whereDate('ngay_tra_phong', now())
            ->with('user', 'datPhongItems.phong')
            ->get();
        return view('staff.checkout', compact('bookings'));
    }

    // public function processCheckout(Request $request)
    // {
    //     $request->validate([
    //         'booking_id' => 'required|exists:dat_phong,id'
    //     ]);
    //     $booking = DatPhong::findOrFail($request->booking_id);
    //     if (!in_array($booking->trang_thai, ['da_gan_phong', 'dang_o'])) {
    //         return redirect()->back()->with('error', 'Booking không thể check-out');
    //     }
    //     DB::transaction(function () use ($booking) {
    //         $booking->update([
    //             'trang_thai' => 'hoan_thanh',
    //             'can_xac_nhan' => false,
    //         ]);
    //         $phongIds = $booking->datPhongItems->pluck('phong_id')->toArray();
    //         Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_don_dep']);
    //         PhongDaDat::whereIn('phong_id', $phongIds)
    //             ->whereIn('dat_phong_item_id', $booking->datPhongItems->pluck('id'))
    //             ->update(['trang_thai' => 'hoan_thanh']);
    //     });
    //     return redirect()->route('staff.rooms')->with('success', 'Check-out thành công cho booking #' . $booking->ma_tham_chieu);
    // }

    public function pendingPayments()
    {
        $pendingPayments = DatPhong::where('can_xac_nhan', true)->get();
        return view('payment.pending_payments', compact('pendingPayments'));
    }

    public function bookings()
    {
        $bookings = DatPhong::with(['nguoiDung', 'datPhongItems.loaiPhong', 'phongDaDats.phong'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('staff.bookings', compact('bookings'));
    }

    public function pendingBookings()
    {
        $bookings = DatPhong::whereIn('trang_thai', ['dang_cho', 'dang_cho_xac_nhan'])
            ->with(['nguoiDung', 'datPhongItems.loaiPhong', 'phongDaDats.phong'])
            ->paginate(10);
        $availableRooms = Phong::where('trang_thai', 'trong')
            ->with(['tang', 'loaiPhong'])
            ->get();
        return view('staff.pending-bookings', compact('bookings', 'availableRooms'));
    }

    public function confirm(Request $request, $id)
    {
        $request->validate([
            'phong_id' => 'nullable|exists:phong,id',
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'staff_id' => 'required|exists:users,id'
        ]);
        $dat_phong = DatPhong::findOrFail($request->dat_phong_id);
        if ($dat_phong->trang_thai !== 'dang_cho_xac_nhan') {
            return response()->json(['error' => 'Booking không thể xác nhận ở trạng thái hiện tại'], 400);
        }
        $booking = DatPhong::with('datPhongItems.loaiPhong')->findOrFail($id);
        if (!in_array($booking->trang_thai, ['dang_cho', 'dang_cho_xac_nhan'])) {
            return redirect()->back()->with('error', 'Booking không thể xác nhận.');
        }
        DB::transaction(function () use ($booking, $request) {

            if (is_null($booking->deposit_amount) || $booking->deposit_amount == 0) {
                $booking->deposit_amount = $request->deposit_amount ?? ($booking->tong_tien * 0.2);
            }
            $booking->trang_thai = 'da_xac_nhan';
            $booking->save();
            $datPhongItem = $booking->datPhongItems->first();
            if (!$datPhongItem) {
                $loaiPhong = LoaiPhong::first();
                if (!$loaiPhong) {
                    throw new \Exception('Chưa có loại phòng nào trong hệ thống.');
                }
                $datPhongItem = DatPhongItem::create([
                    'dat_phong_id' => $booking->id,
                    'loai_phong_id' => $loaiPhong->id,
                    'so_luong' => 1,
                    'gia_tren_dem' => $loaiPhong->gia_tren_dem ?? 0,
                    'so_dem' => $loaiPhong->so_dem ?? 1,
                ]);
            }
            if ($request->filled('phong_id')) {
                $phong_id = $request->phong_id;
                if ($this->checkAvailability($phong_id, $booking->ngay_nhan_phong, $booking->ngay_tra_phong)) {
                    PhongDaDat::create([
                        'dat_phong_item_id' => $datPhongItem->id,
                        'phong_id' => $phong_id,
                        'trang_thai' => 'da_dat',
                        'checkin_datetime' => $booking->ngay_nhan_phong,
                        'checkout_datetime' => $booking->ngay_tra_phong,
                    ]);
                    Phong::find($phong_id)->update(['trang_thai' => 'da_dat']);
                    $booking->trang_thai = 'da_gan_phong';
                    $booking->save();
                } else {
                    throw new \Exception('Phòng không khả dụng.');
                }
            }
        });
        if ($booking->trang_thai === 'da_gan_phong') {
            return redirect()->route('staff.index')
                ->with('success', 'Booking đã được xác nhận và gán phòng thành công.');
        }
        return redirect()->route('staff.assign-rooms', $booking->id)
            ->with('success', 'Booking đã được xác nhận. Vui lòng tiến hành gán phòng.');
    }

    protected function checkAvailability($phong_id, $start, $end)
    {
        $currentTime = now();
        $overlappingBookings = PhongDaDat::where('phong_id', $phong_id)
            ->where('trang_thai', '!=', 'da_huy')
            ->where('checkin_datetime', '<', $end)
            ->where('checkout_datetime', '>', $start)
            ->count();
        $holds = GiuPhong::where('phong_id', $phong_id)
            ->where('het_han_luc', '>', $currentTime)
            ->where('released', false)
            ->count();
        return ($overlappingBookings + $holds) == 0;
    }

    public function rooms(Request $request)
    {
        $query = Phong::with(['tang', 'datPhongItems.datPhong.nguoiDung']);

        // Lọc theo mã phòng
        if ($ma_phong = $request->input('ma_phong')) {
            $query->where('ma_phong', 'like', '%' . $ma_phong . '%');
        }


        if ($trang_thai = $request->input('trang_thai')) {
            if (in_array($trang_thai, ['dang_su_dung', 'dang_o', 'da_xac_nhan'])) {

                $query->whereHas('datPhongItems.datPhong', function ($q) use ($trang_thai) {
                    $q->where('trang_thai', $trang_thai);
                });
            } else {

                $query->where('trang_thai', $trang_thai);
            }
        }

        $rooms = $query->orderBy('ma_phong')->paginate(10);

        return view('staff.rooms', compact('rooms'));
    }




    public function updateRoom(Request $request, Phong $room)
    {
        $request->validate([
            'trang_thai' => 'required|in:trong,dang_o,dang_don_dep',
        ]);
        $room->update([
            'trang_thai' => $request->trang_thai,
        ]);
        return redirect()->route('staff.rooms')->with('success', 'Cập nhật trạng thái phòng ' . $room->ma_phong . ' thành công.');
    }

    public function showBooking(DatPhong $booking)
    {
        $booking->load(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'nguoiDung', 'giaoDichs']);
        $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true) ?? [];
        return view('staff.bookings.show', compact('booking', 'meta'));
    }

    public function cancel($id)
    {
        $booking = DatPhong::findOrFail($id);
        if (!in_array($booking->trang_thai, ['dang_cho', 'dang_cho_xac_nhan'])) {
            return redirect()->back()->with('error', 'Chỉ có thể hủy booking chờ xác nhận.');
        }
        $booking->update(['trang_thai' => 'da_huy']);
        return redirect()->route('staff.pending-bookings')->with('success', 'Booking đã được hủy thành công.');
    }
}
