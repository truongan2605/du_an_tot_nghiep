<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\DanhGiaSpace;
use Illuminate\Support\Facades\Auth;

class DanhGiaController extends Controller
{
    public function store(Request $request, $phongId)
    {
        $user = Auth::user();

        // Lấy đơn đặt phòng đã checkout
        $datPhong = DatPhong::where('phong_id', $phongId)
            ->where('user_id', $user->id)
            ->where('trang_thai', 'da_checkout')
            ->first();

        if (!$datPhong) {
            return back()->with('error', 'Bạn chỉ có thể đánh giá sau khi đã trả phòng.');
        }

        // Kiểm tra đã đánh giá chưa
        $daDanhGia = DanhGiaSpace::where('dat_phong_id', $datPhong->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($daDanhGia) {
            return back()->with('error', 'Bạn đã đánh giá phòng này rồi.');
        }

        // Validate
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'noi_dung' => 'required|string',
        ]);

        // Lưu đánh giá
        DanhGiaSpace::create([
            'phong_id' => $phongId,
            'user_id' => $user->id,
            'dat_phong_id' => $datPhong->id,
            'rating' => $request->rating,
            'noi_dung' => $request->noi_dung,
        ]);

        return back()->with('success', 'Cảm ơn bạn đã đánh giá phòng!');
    }
}
