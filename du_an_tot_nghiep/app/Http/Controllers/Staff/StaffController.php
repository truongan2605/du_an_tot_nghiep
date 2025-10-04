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
        ]);

        $booking = DatPhong::with('datPhongItems.loaiPhong')->findOrFail($id);

        if ($booking->trang_thai !== 'dang_cho') {
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
            return redirect()->route('staff.rooms')->with('success', 'Booking đã được gán phòng thành công.');
        } else {
            return redirect()->route('staff.assign-rooms', $booking->id)->with('success', 'Booking đã được xác nhận.');
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

    // --- Assign Rooms Form ---
    public function assignRoomsForm($dat_phong_id)
    {
        $booking = DatPhong::with('datPhongItems.loaiPhong')->findOrFail($dat_phong_id);

        if ($booking->trang_thai !== 'da_xac_nhan') {
            return redirect()->back()->with('error', 'Booking chưa được xác nhận.');
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

    // --- Assign Rooms Save ---
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

    public function cancel($id)
    {
        $booking = DatPhong::findOrFail($id);
        if ($booking->trang_thai !== 'dang_cho') {
            return redirect()->back()->with('error', 'Chỉ có thể hủy booking chờ xác nhận.');
        }

        $booking->update(['trang_thai' => 'da_huy']);
        return redirect()->route('staff.pending-bookings')->with('success', 'Booking đã được hủy thành công.');
    }
}
