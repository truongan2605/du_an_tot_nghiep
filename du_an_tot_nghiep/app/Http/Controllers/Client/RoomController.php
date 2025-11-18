<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\DanhGia;
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\DatPhong;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request)
{
    $perPage = 9;

    // Query gốc: lấy phòng để áp filter
    $query = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis'])
        ->orderByDesc('created_at');

    // =============== Lọc theo loại phòng ===============
    if ($request->filled('loai_phong_id')) {
        $query->where('loai_phong_id', $request->loai_phong_id);
    }

    // =============== Lọc theo khoảng giá preset ===============
    if ($request->filled('gia_khoang')) {
        switch ($request->gia_khoang) {
            case '1':
                $query->where('gia_mac_dinh', '<', 500000);
                break;
            case '2':
                $query->whereBetween('gia_mac_dinh', [500000, 1000000]);
                break;
            case '3':
                $query->whereBetween('gia_mac_dinh', [1000000, 1500000]);
                break;
            case '4':
                $query->where('gia_mac_dinh', '>', 1500000);
                break;
        }
    }

    // =============== Lọc theo giá slider ===============
    if ($request->filled('gia_min') && $request->filled('gia_max')) {
        $query->whereBetween('gia_cuoi_cung', [
            $request->gia_min,
            $request->gia_max,
        ]);
    }

    // =============== Lọc theo tiện nghi ===============
    if ($request->filled('tien_nghi')) {
        $tienNghiIds = (array) $request->tien_nghi;
        $query->whereHas('tienNghis', function ($q) use ($tienNghiIds) {
            $q->whereIn('tien_nghi.id', $tienNghiIds);
        });
    }

    // Sau khi áp tất cả filter -> lấy danh sách phòng
    $allRooms = $query->get();

    // Nhóm theo loại phòng
    $groupedByType = $allRooms->groupBy('loai_phong_id');

    // Tổng số phòng / loại
    $totalRoomsByType = $groupedByType->map(function ($group) {
        return $group->count();
    });

    // ===== Tính số phòng TRỐNG theo loại phòng trong khoảng ngày đã chọn (nếu có) =====
    $availableByType = [];
    $checkIn = null;
    $checkOut = null;

    if ($request->filled('date_range')) {
        $dates = explode(' to ', $request->date_range);
        if (count($dates) === 2) {
            try {
                $checkIn = Carbon::parse(trim($dates[0]))->startOfDay();
                $checkOut = Carbon::parse(trim($dates[1]))->startOfDay();
            } catch (\Throwable $e) {
                $checkIn = $checkOut = null;
            }
        }
    }

    if ($checkIn && $checkOut) {
        $from = $checkIn->toDateString();
        $to   = $checkOut->toDateString();

        // Đếm số PHÒNG đã bận (có booking trùng ngày) theo LOẠI PHÒNG
        $busyByType = DB::table('dat_phong')
            ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
            ->join('phong', 'phong.id', '=', 'dat_phong_item.phong_id')
            // tránh trùng tên cột -> prefix đầy đủ
            ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('dat_phong.ngay_nhan_phong', [$from, $to])
                  ->orWhereBetween('dat_phong.ngay_tra_phong', [$from, $to])
                  ->orWhere(function ($q2) use ($from, $to) {
                      // booking bao phủ toàn bộ khoảng chọn
                      $q2->where('dat_phong.ngay_nhan_phong', '<=', $from)
                         ->where('dat_phong.ngay_tra_phong', '>=', $to);
                  });
            })
            ->selectRaw('phong.loai_phong_id, COUNT(DISTINCT phong.id) as so_phong_ban')
            ->groupBy('phong.loai_phong_id')
            ->pluck('so_phong_ban', 'phong.loai_phong_id')
            ->toArray();

        foreach ($totalRoomsByType as $typeId => $totalCount) {
            $busy = $busyByType[$typeId] ?? 0;
            $availableByType[$typeId] = max($totalCount - $busy, 0);
        }
    }

    // Tạo collection loại phòng: 1 phòng đại diện + số lượng / số phòng trống
    $roomTypeCollection = $groupedByType->map(function ($group, $typeId) use ($availableByType) {
        /** @var \App\Models\Phong $room */
        $room = $group->first();
        $room->so_luong_phong_cung_loai = $group->count();
        // nếu chưa chọn ngày => so_phong_trong = null
        $room->so_phong_trong = $availableByType[$typeId] ?? null;
        return $room;
    })->values();

    // Phân trang theo loại phòng
    $page = LengthAwarePaginator::resolveCurrentPage();
    $total = $roomTypeCollection->count();
    $results = $roomTypeCollection->slice(($page - 1) * $perPage, $perPage)->values();

    $phongs = new LengthAwarePaginator(
        $results,
        $total,
        $perPage,
        $page,
        [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]
    );

    // Dữ liệu sidebar
    $loaiPhongs = LoaiPhong::all();
    $tienNghis = TienNghi::where('active', 1)->get();
    $giaMin = 0;
    $giaMax = Phong::max('gia_cuoi_cung');

    return view('list-room', compact(
        'phongs',
        'loaiPhongs',
        'tienNghis',
        'giaMin',
        'giaMax',
        'checkIn',
        'checkOut'
    ));
}



    public function show($id)
    {
        $phong = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis', 'bedTypes'])->findOrFail($id);

        $related = Phong::with('images')
            ->where('loai_phong_id', $phong->loai_phong_id)
            ->where('id', '<>', $phong->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Ratings logic (giữ nguyên)
        $avgRating = 0;
        $reviews = collect();

        if (Schema::hasTable('dat_phong') && Schema::hasColumn('dat_phong', 'phong_id')) {
            $avgRating = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->avg('danh_gia.diem');

            $reviews = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->select('danh_gia.*')
                ->orderByDesc('danh_gia.created_at')
                ->get();
        } elseif (Schema::hasTable('dat_phong_items') && Schema::hasColumn('dat_phong_items', 'phong_id')) {
            $avgRating = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->join('dat_phong_items', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->avg('danh_gia.diem');

            $reviews = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->join('dat_phong_items', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->select('danh_gia.*')
                ->orderByDesc('danh_gia.created_at')
                ->get();
        } elseif (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'phong_id')) {
            $avgRating = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->avg('diem');

            $reviews = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->orderByDesc('created_at')
                ->get();
        } else {
            $avgRating = 0;
            $reviews = collect();
        }

        $avgRating = $avgRating ? round(floatval($avgRating), 1) : 0.0;

        $bedSummary = collect();
        $totalBeds = 0;

        if ($phong->relationLoaded('bedTypes') && $phong->bedTypes->count()) {
            foreach ($phong->bedTypes as $bt) {
                $qty = (int) ($bt->pivot->quantity ?? 0);
                if ($qty <= 0) continue;
                $price = $bt->pivot->price !== null ? (float) $bt->pivot->price : (float) ($bt->price ?? 0);
                $bedSummary->push([
                    'id' => $bt->id,
                    'name' => $bt->name ?? ($bt->title ?? 'Bed'),
                    'quantity' => $qty,
                    'price' => $price,
                    'capacity' => $bt->capacity ?? null,
                    'icon' => $bt->icon ?? null,
                ]);
                $totalBeds += $qty;
            }
        }

        if ($totalBeds <= 0) {
            $totalBeds = (int) ($phong->so_giuong ?? $phong->loaiPhong->so_giuong ?? 0);
        }

        $isWished = false;
        if (Auth::check()) {
            $isWished = Wishlist::where('user_id', Auth::id())
                ->where('phong_id', $phong->id)
                ->exists();
        }

        return view('detail-room', compact('phong', 'related', 'avgRating', 'reviews', 'bedSummary', 'totalBeds', 'isWished'));
    }
}
