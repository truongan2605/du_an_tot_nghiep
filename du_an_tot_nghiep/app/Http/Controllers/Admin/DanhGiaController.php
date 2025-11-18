<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\DanhGiaSpace;

class DanhGiaController extends Controller
{
    public function index()
    {
        $phongs = Phong::withAvg('danhGiaSpaces', 'rating') // tính trung bình sao
            ->withCount(['danhGiaSpaces as so_danh_gia_moi' => function ($query) {
                $query->where('is_new', 1);
            }])
            ->get();

        return view('admin.danhgia.index', compact('phongs'));
    }

    public function show($phong_id)
    {
        $danhGias = DanhGiaSpace::where('phong_id', $phong_id)
            ->whereNull('parent_id')
            ->with(['user', 'children.user'])
            ->latest()
            ->get();

        // Khi admin vào xem, đánh dấu đã xem
        DanhGiaSpace::where('phong_id', $phong_id)->update(['is_new' => 0]);

        $phong = Phong::find($phong_id);

        return view('admin.danhgia.show', compact('phong', 'danhGias'));
    }
}
