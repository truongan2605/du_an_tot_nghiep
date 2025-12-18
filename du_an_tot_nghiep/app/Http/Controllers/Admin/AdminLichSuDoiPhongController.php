<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatPhong;
use App\Models\LichSuDoiPhong;

class AdminLichSuDoiPhongController extends Controller
{
    /**
     * Hiển thị lịch sử đổi phòng của 1 booking
     */
    public function index($datPhongId)
    {
        $booking = DatPhong::findOrFail($datPhongId);

        $lichSuDoiPhong = LichSuDoiPhong::where('dat_phong_id', $datPhongId)
            ->with(['phongCu', 'phongMoi'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.dat-phong.lich-su-doi-phong', compact(
            'booking',
            'lichSuDoiPhong'
        ));
    }
}
