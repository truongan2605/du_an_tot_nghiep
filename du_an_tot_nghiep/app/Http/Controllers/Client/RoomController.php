<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\DanhGia;
use Illuminate\Support\Facades\Schema;

class RoomController extends Controller
{
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
        elseif (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'phong_id')) {
            $avgRating = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->avg('diem');

            $reviews = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->orderByDesc('created_at')
                ->get();
        }
        else {
            $avgRating = 0;
            $reviews = collect();
        }

        $avgRating = $avgRating ? round(floatval($avgRating), 1) : 0.0;

        return view('detail-room', compact('phong','related','avgRating','reviews'));
    }
}
