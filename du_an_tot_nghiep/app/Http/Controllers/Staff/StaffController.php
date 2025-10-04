<?php

namespace App\Http\Controllers\Staff;

use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiuPhong;
use App\Models\PhongDaDat;
use App\Models\DatPhongItem;
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
            ->with('user')
            ->get()
            ->map(function ($booking) {
                return [
                    'title' => 'BK-' . $booking->ma_tham_chieu,
                    'start' => $booking->ngay_nhan_phong,
                    'end' => $booking->ngay_tra_phong,
                    'description' => "Khách: " . ($booking->user->name ?? 'Ẩn danh') .
                        " | Trạng thái: " . $booking->trang_thai
                ];
            });


        $recentActivities = DatPhong::where('trang_thai', '!=', 'dang_cho')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('staff.index', compact(
            'pendingBookings',
            'todayCheckins',
            'todayRevenue',
            'events',
            'recentActivities'
        ));
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
        $bookings = DatPhong::where('trang_thai', 'dang_cho')
            ->with(['user', 'datPhongItems.loaiPhong', 'phongDaDats.phong'])
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
        ]);

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
                        'dat_phong_item_id' => $booking->datPhongItems->first()->id,
                        'phong_id' => $phong_id,
                        'trang_thai' => 'da_dat',
                        'checkin_datetime' => $booking->ngay_nhan_phong,
                        'checkout_datetime' => $booking->ngay_tra_phong,
                    ]);
                    Phong::find($phong_id)->update(['trang_thai' => 'da_dat']);
                    $booking->update(['trang_thai' => 'da_gan_phong']);
                } else {
                    throw new \Exception('Phòng không khả dụng.');
                }
            }
        });

        $redirectTo = $request->filled('phong_id') && $this->checkAvailability($request->phong_id, $booking->ngay_nhan_phong, $booking->ngay_tra_phong)
            ? route('staff.rooms')
            : route('staff.assign-rooms', $booking->id);
        return redirect($redirectTo)->with('success', 'Booking đã được xác nhận' . ($request->filled('phong_id') ? ' và gán phòng' : '') . '.');
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
        $booking = DatPhong::findOrFail($dat_phong_id);
        if ($booking->trang_thai !== 'da_xac_nhan') {
            return redirect()->back()->with('error', 'Booking chưa được xác nhận.');
        }

        $items = DatPhongItem::where('dat_phong_id', $dat_phong_id)->with('loaiPhong')->get();
        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Không có item nào trong booking để gán phòng.');
        }

        $availableRooms = Phong::with(['tang', 'loaiPhong'])
            ->whereIn('trang_thai', ['trong', 'da_dat'])
            ->orderByRaw("CASE WHEN trang_thai = 'da_dat' THEN 0 ELSE 1 END")
            ->get();

        return view('staff.assign-rooms', compact('booking', 'items', 'availableRooms'));
    }
    public function assignRooms(Request $request, $dat_phong_id)
    {
        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.dat_phong_item_id' => 'required|exists:dat_phong_item,id',
            'assignments.*.phong_id' => 'required|exists:phong,id',
        ]);

        $booking = DatPhong::findOrFail($dat_phong_id);
        if ($booking->trang_thai !== 'da_xac_nhan') {
            return redirect()->back()->with('error', 'Booking chưa được xác nhận.');
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
                        'dat_phong_item_id' => $assign['dat_phong_item_id'],
                        'phong_id' => $assign['phong_id'],
                        'trang_thai' => 'da_dat',
                        'checkin_datetime' => $booking->ngay_nhan_phong,
                        'checkout_datetime' => $booking->ngay_tra_phong,
                    ]);
                    Phong::find($assign['phong_id'])->update(['trang_thai' => 'da_dat']);
                    $assigned[] = "Item {$assign['dat_phong_item_id']} assigned to room {$assign['phong_id']}.";
                } else {
                    $conflicts[] = "Phòng {$assign['phong_id']} không khả dụng.";
                }
            }
        });

        if (empty($conflicts)) {
            $booking->update(['trang_thai' => 'da_gan_phong']);
        }

        $message = count($conflicts) > 0 ? 'Partial success: ' . implode(', ', $conflicts) : 'Tất cả đã được gán thành công.';
        return redirect()->route('staff.rooms')->with('success', $message)->with('conflicts', $conflicts);
    }

    public function rooms()
    {
        $rooms = PhongDaDat::where('trang_thai', 'da_dat')
            ->with(['phong.tang', 'datPhongItem.datPhong.user', 'datPhongItem.loaiPhong'])
            ->paginate(10);
        return view('staff.rooms', compact('rooms'));
    }
}
