<?php
namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\DanhGia;
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use Illuminate\Support\Facades\Schema;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Phong::with(['loaiPhong', 'tang', 'images'])
        ->orderByDesc('created_at');

    if ($request->filled('loai_phong_id')) {
        $query->where('loai_phong_id', $request->loai_phong_id);
    }
     // Lọc theo khoảng giá
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
    // =============== Lọc theo loại phòng ===============
        if ($request->filled('loai_phong_id')) {
            $query->where('loai_phong_id', $request->loai_phong_id);
        }
        // =============== Lọc theo giá ===============
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
        // =============== Lọc theo tiện nghi ===============
        if ($request->filled('amenities')) {
    $query->whereHas('tienNghis', function ($q) use ($request) {
        $q->whereIn('tien_nghi.id', $request->amenities);
    });
}
        // =============== Lọc theo trạng thái phòng / ngày ===============
        if ($request->filled('check_in_out')) {
            $dates = explode(' to ', $request->check_in_out);
            if (count($dates) === 2) {
                $query->where('trang_thai', 'Trống');
            }
        }
    $phongs = $query->paginate(9);
    $loaiPhongs = LoaiPhong::all();
    $tienNghis = TienNghi::where('active', 1)->get();
    return view('list-room', compact('phongs', 'loaiPhongs','tienNghis'));
    }
    public function show($id)
    {
        $phong = Phong::with(['loaiPhong','tang','images','tienNghis'])->findOrFail($id);

        $related = Phong::with('images')
            ->where('loai_phong_id', $phong->loai_phong_id)
            ->where('id', '<>', $phong->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $avgRating = 0;
        $reviews = collect();

        // 1) nếu dat_phong có cột phong_id (booking trực tiếp gắn phong_id)
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
        }
        // 2) nếu dat_phong_items tồn tại và chứa phong_id (booking có nhiều item, mỗi item gắn phong_id)
        elseif (Schema::hasTable('dat_phong_items') && Schema::hasColumn('dat_phong_items', 'phong_id')) {
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
        }
        // 3) nếu danh_gia trực tiếp có cột phong_id (không qua booking)
        elseif (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'phong_id')) {
            $avgRating = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->avg('diem');

            $reviews = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->orderByDesc('created_at')
                ->get();
        }
        // 4) fallback: không có cấu trúc phù hợp -> trả về rỗng (không throw)
        else {
            $avgRating = 0;
            $reviews = collect();
        }

        $avgRating = $avgRating ? round(floatval($avgRating), 1) : 0.0;

        return view('detail-room', compact('phong','related','avgRating','reviews'));
    }
}
