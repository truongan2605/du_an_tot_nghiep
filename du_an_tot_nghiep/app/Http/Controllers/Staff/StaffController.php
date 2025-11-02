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
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
 public function index()
    {
        $today = now();
        $weekRange = [now()->startOfWeek(), now()->endOfWeek()];

        $pendingBookings = DatPhong::where('trang_thai', 'dang_cho')->count();
        $todayCheckins = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereDate('ngay_nhan_phong', $today)->count();

        $todayRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereDate('ngay_nhan_phong', $today)
            ->sum('tong_tien');

        $weeklyRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereBetween('ngay_nhan_phong', $weekRange)
            ->sum('tong_tien');

        $monthlyRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereMonth('ngay_nhan_phong', $today->month)
            ->sum('tong_tien');

        $totalRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')->sum('tong_tien');
        $availableRooms = Phong::where('trang_thai', 'trong')->count();

       
        $events = DatPhong::select('id', 'ma_tham_chieu', 'trang_thai', 'ngay_nhan_phong', 'ngay_tra_phong')
            ->where('trang_thai', '!=', 'da_huy')
            ->with('user:id,name')
            ->get()
            ->map(fn($b) => [
                'title' => 'BK-' . $b->ma_tham_chieu,
                'start' => $b->ngay_nhan_phong,
                'end' => $b->ngay_tra_phong,
                'description' => "Khách: " . ($b->user->name ?? 'Ẩn danh') . " | " . ucfirst($b->trang_thai)
            ]);

        $recentActivities = DatPhong::where('trang_thai', '!=', 'dang_cho')
            ->orderByDesc('updated_at')->take(20)->get();

       
        $chartLabels = [];
        $checkinData = [];
        $checkoutData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $chartLabels[] = $today->copy()->subDays($i)->format('d/m');
            $checkinData[] = DatPhong::where('trang_thai', 'da_xac_nhan')->whereDate('ngay_nhan_phong', $date)->count();
            $checkoutData[] = DatPhong::where('trang_thai', 'da_xac_nhan')->whereDate('ngay_tra_phong', $date)->count();
        }

        return view('staff.index', compact(
            'pendingBookings', 'todayCheckins', 'todayRevenue',
            'weeklyRevenue', 'monthlyRevenue', 'totalRevenue',
            'availableRooms', 'events', 'recentActivities',
            'chartLabels', 'checkinData', 'checkoutData'
        ));
    }


public function reports()
    {
        $month = now()->month;

        $monthlyRevenue = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereMonth('ngay_nhan_phong', $month)
            ->sum('tong_tien');

        $bookingsThisMonth = DatPhong::where('trang_thai', 'da_xac_nhan')
            ->whereMonth('ngay_nhan_phong', $month)->count();

        $availableRooms = Phong::where('trang_thai', 'trong')->count();

       
        $weeklyRevenue = [];
        $weeklyBookings = [];

        for ($i = 0; $i < 4; $i++) {
            $start = now()->startOfMonth()->addWeeks($i);
            $end = $start->copy()->endOfWeek();

            $weeklyRevenue[] = DatPhong::where('trang_thai', 'da_xac_nhan')
                ->whereBetween('ngay_nhan_phong', [$start, $end])
                ->sum('tong_tien');

            $weeklyBookings[] = DatPhong::where('trang_thai', 'da_xac_nhan')
                ->whereBetween('ngay_nhan_phong', [$start, $end])
                ->count();
        }

        return view('staff.reports', compact(
            'monthlyRevenue', 'bookingsThisMonth',
            'availableRooms', 'weeklyRevenue', 'weeklyBookings'
        ));
    }

public function roomOverview()
{
   
    $rooms = Phong::with(['tang', 'loaiPhong'])
                  ->orderBy('tang_id')
                  ->orderBy('ma_phong')
                  ->get();

    
    $floors = $rooms->groupBy(function($room){
        return $room->tang->so_tang ?? $room->tang->id;
    });

    return view('staff.room-overview', compact('floors'));
}

public function checkinForm()
{
   
    $bookings = DatPhong::where('trang_thai', 'da_xac_nhan')
                ->whereDate('ngay_nhan_phong', '<=', now())
                ->with('user', 'datPhongItems.loaiPhong')
                ->get();

    return view('staff.checkin', compact('bookings'));
}

public function processCheckin(Request $request)
{
    $request->validate([
        'booking_id' => 'required|exists:dat_phong,id'
    ]);

    $booking = DatPhong::findOrFail($request->booking_id);

    if ($booking->trang_thai != 'da_xac_nhan') {
        return redirect()->back()->with('error', 'Booking không thể check-in');
    }

    $booking->trang_thai = 'dang_o';
    $booking->save();

    return redirect()->route('staff.dashboard')->with('success', 'Check-in thành công');
}



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


// {
//     // Dữ liệu thống kê chính
//     $pendingBookings = 5;
//     $todayCheckins = 8;
//     $todayRevenue = 12000000;
//     $availableRooms = 12;

   
//     $recentActivities = collect([
//         (object)['ma_tham_chieu'=>'BK001','trang_thai'=>'dang_cho','updated_at'=>now()->subMinutes(10)],
//         (object)['ma_tham_chieu'=>'BK002','trang_thai'=>'da_xac_nhan','updated_at'=>now()->subHours(1)],
//         (object)['ma_tham_chieu'=>'BK003','trang_thai'=>'da_gan_phong','updated_at'=>now()->subHours(3)],
//         (object)['ma_tham_chieu'=>'BK004','trang_thai'=>'da_huy','updated_at'=>now()->subDays(1)],
//     ]);

 
//     $chartLabels = ['1/10', '2/10', '3/10', '4/10', '5/10', '6/10', '7/10'];
//     $checkinData = [5, 7, 6, 8, 4, 9, 7];
//     $checkoutData = [3, 6, 4, 5, 3, 8, 6];

   
//     $events = [
//         ['title'=>'Booking BK001','start'=>now()->subDays(1)->toDateString(),'description'=>'Check-in BK001'],
//         ['title'=>'Booking BK002','start'=>now()->toDateString(),'description'=>'Check-in BK002'],
//     ];

//     return view('staff.dashboard', compact(
//         'pendingBookings',
//         'todayCheckins',
//         'todayRevenue',
//         'availableRooms',
//         'recentActivities',
//         'chartLabels',
//         'checkinData',
//         'checkoutData',
//         'events'
//     ));
// }


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
            ->with('success', 'Booking đã được xác nhận và gán phòng thành công. Dashboard đã được cập nhật.');
    } else {
        return redirect()->route('staff.assign-rooms', $booking->id)
            ->with('success', 'Booking đã được xác nhận. Vui lòng tiến hành gán phòng.');
    }
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

    public function assignRoomsForm($dat_phong_id)
    {
        $booking = DatPhong::with('datPhongItems.loaiPhong')->findOrFail($dat_phong_id);

        if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
            return redirect()->back()->with('error', 'Booking chưa được xác nhận hoặc không thể gán phòng.');
        }

        if ($booking->datPhongItems->isEmpty()) {
            $loaiPhong = LoaiPhong::first();
            if ($loaiPhong) {
                DatPhongItem::create([
                    'dat_phong_id' => $booking->id,
                    'loai_phong_id' => $loaiPhong->id,
                    'so_luong' => 1,
                    'gia_tren_dem' => $loaiPhong->gia_tren_dem ?? 0,
                    'so_dem' => $loaiPhong->so_dem ?? 1,
                ]);
                $booking->load('datPhongItems.loaiPhong');
            }
        }

        $availableRooms = Phong::with(['tang', 'loaiPhong'])
            ->whereIn('trang_thai', ['trong', 'da_dat'])
            ->orderByRaw("CASE WHEN trang_thai = 'da_dat' THEN 0 ELSE 1 END")
            ->get();

        return view('staff.assign-rooms', [
            'booking' => $booking,
            'items' => $booking->datPhongItems,
            'availableRooms' => $availableRooms
        ]);
    }

    public function assignRooms(Request $request, $dat_phong_id)
{
    $request->validate([
        'assignments' => 'required|array|min:1',
        'assignments.*.dat_phong_item_id' => 'required|exists:dat_phong_item,id',
        'assignments.*.phong_id' => 'required|exists:phong,id',
    ]);

    $booking = DatPhong::findOrFail($dat_phong_id);
    if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
        return redirect()->back()->with('error', 'Booking chưa được xác nhận hoặc không thể gán phòng.');
    }

    $assigned = [];
    $conflicts = [];

    DB::transaction(function () use ($request, $booking, &$assigned, &$conflicts) {
        foreach ($request->assignments as $assign) {
            $item = DatPhongItem::findOrFail($assign['dat_phong_item_id']);

            if ($item->dat_phong_id !== $booking->id) {
                $conflicts[] = "Item ID {$assign['dat_phong_item_id']} không thuộc booking.";
                continue;
            }

            if ($this->checkAvailability($assign['phong_id'], $booking->ngay_nhan_phong, $booking->ngay_tra_phong)) {
                PhongDaDat::create([
                    'dat_phong_item_id' => $item->id,
                    'phong_id' => $assign['phong_id'],
                    'trang_thai' => 'da_dat',
                    'checkin_datetime' => $booking->ngay_nhan_phong,
                    'checkout_datetime' => $booking->ngay_tra_phong,
                ]);
                Phong::find($assign['phong_id'])->update(['trang_thai' => 'da_dat']);
                $assigned[] = "Item {$item->id} assigned to room {$assign['phong_id']}.";
            } else {
                $conflicts[] = "Phòng {$assign['phong_id']} không khả dụng.";
            }
        }
    });

    if (empty($conflicts)) {
        $booking->update(['trang_thai' => 'da_gan_phong']);
    }

    
    $message = count($conflicts) > 0
        ? 'Gán phòng thành công một phần: ' . implode(', ', $conflicts)
        : 'Tất cả phòng đã được gán thành công!';

    return redirect()->route('staff.index')
        ->with('success', $message)
        ->with('conflicts', $conflicts);
}


    public function rooms()
    {
        $roomsQuery = PhongDaDat::where('trang_thai', 'da_dat')
            ->with(['phong.tang', 'datPhongItem.datPhong.user', 'datPhongItem.loaiPhong']);
        $rooms = $roomsQuery->orderBy('updated_at', 'desc')->paginate(10);
        $latestRoom = $roomsQuery->orderBy('updated_at', 'desc')->first();
        return view('staff.rooms', compact('rooms', 'latestRoom'));
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