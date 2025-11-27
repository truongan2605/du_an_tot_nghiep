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
 public function create($bookingId)
{
    $booking = DatPhong::with('phongs')
        ->where('id', $bookingId)
        ->where('nguoi_dung_id', Auth::id())
        ->firstOrFail();

    if ($booking->trang_thai !== 'hoan_thanh') {
        return back()->with('error', 'Chỉ được đánh giá khi đơn đã hoàn thành.');
    }

    return view('account.danhgia.create', compact('booking'));
}



    // Lưu đánh giá
  public function store(Request $request, $bookingId)
{
    $booking = DatPhong::with('phongs')
        ->where('id', $bookingId)
        ->where('nguoi_dung_id', Auth::id())
        ->firstOrFail();

    if ($booking->trang_thai !== 'hoan_thanh') {
        return back()->with('error', 'Chỉ được đánh giá khi đơn đã hoàn thành.');
    }

    $request->validate([
        'rating' => 'nullable|integer|min:1|max:5', // có thể null nếu chỉ comment
        'noi_dung' => 'required|string'
    ]);

    foreach ($booking->phongs as $phong) {
        // Kiểm tra xem phòng này đã được đánh giá sao chưa
        $existingRating = DanhGiaSpace::where('dat_phong_id', $booking->id)
            ->where('user_id', Auth::id())
            ->where('phong_id', $phong->id)
            ->whereNotNull('rating')
            ->first();

        DanhGiaSpace::create([
            'phong_id' => $phong->id,
            'user_id' => Auth::id(),
            'dat_phong_id' => $booking->id,
            // nếu đã đánh giá sao rồi thì để null
            'rating' => $existingRating ? null : $request->rating,
            'noi_dung' => $request->noi_dung,
        ]);
    }

    return back()->with('success', 'Cảm ơn bạn đã đánh giá!');
}


}
