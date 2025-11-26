<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSpace;
use App\Models\DatPhong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DanhGiaController extends Controller
{
    // Hiển thị form đánh giá
    public function create($datPhongId)
    {
        $booking = DatPhong::with('phong')->findOrFail($datPhongId);

        if ($booking->user_id != Auth::id()) {
            abort(403);
        }

        // Chỉ đánh giá khi đã checkout
        if ($booking->trang_thai !== 'da_checkout') {
            return back()->with('error', 'Bạn chỉ có thể đánh giá sau khi đã trả phòng.');
        }

        return view('account.danhgia.form', compact('booking'));
    }

    // Lưu đánh giá vào DB
    public function store(Request $request, $datPhongId)
    {
        $booking = DatPhong::with('phong')->findOrFail($datPhongId);

        if ($booking->user_id != Auth::id()) {
            abort(403);
        }

        if ($booking->trang_thai !== 'da_checkout') {
            return back()->with('error', 'Bạn chưa hoàn thành chuyến lưu trú.');
        }

        // Kiểm tra đã đánh giá chưa
        $alreadyRated = DanhGiaSpace::where('dat_phong_id', $booking->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyRated) {
            return back()->with('error', 'Bạn đã đánh giá phòng này rồi.');
        }

        // Validate
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'noi_dung' => 'required|string|max:1000',
        ]);

        // Tạo đánh giá
        DanhGiaSpace::create([
            'phong_id'      => $booking->phong_id,
            'user_id'       => Auth::id(),
            'dat_phong_id'  => $booking->id,
            'rating'        => $request->rating,
            'noi_dung'      => $request->noi_dung,
            'is_new'        => 1,
        ]);

        return redirect()->route('account.booking.index')
            ->with('success', 'Cảm ơn bạn đã đánh giá!');
    }
}
