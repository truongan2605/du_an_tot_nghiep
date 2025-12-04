<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSpace;
use App\Models\DatPhong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 use Illuminate\Support\Facades\DB; // ở đầu file nếu chưa có

class DanhGiaController extends Controller
{
    // Hiển thị form đánh giá
    public function create(DatPhong $booking)
    {
        // Kiểm tra booking thuộc user
        if ($booking->nguoi_dung_id !== Auth::id()) {
            abort(404);
        }

        // Kiểm tra trạng thái đơn
        if ($booking->trang_thai !== 'hoan_thanh') {
            return back()->with('error', 'Chỉ được đánh giá khi đơn đã hoàn thành.');
        }

        $booking->load('phongs'); // Quan hệ nhiều phòng

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
        'rating' => 'nullable|integer|min:1|max:5',
        'noi_dung' => 'required|string'
    ]);

    // Lấy danh sách phòng: ưu tiên relation phongs(), nếu rỗng thì lấy từ history
    $phongs = $booking->phongs()->get();

    if ($phongs->isEmpty()) {
        // Lấy phong_id từ bảng history
        $historyIds = DB::table('dat_phong_items_history')
            ->where('dat_phong_id', $booking->id)
            ->pluck('phong_id')
            ->toArray();

        if (!empty($historyIds)) {
            $phongs = \App\Models\Phong::whereIn('id', $historyIds)->get();
        }
    }

    if ($phongs->isEmpty()) {
        // fallback: lấy dat_phong_item trực tiếp (nếu chưa xóa)
        $itemIds = DB::table('dat_phong_item')->where('dat_phong_id', $booking->id)->pluck('phong_id')->toArray();
        if (!empty($itemIds)) {
            $phongs = \App\Models\Phong::whereIn('id', $itemIds)->get();
        }
    }

    // nếu vẫn trống -> lỗi rõ ràng
    if ($phongs->isEmpty()) {
        return back()->with('error', 'Không tìm thấy phòng để đánh giá (vui lòng liên hệ admin).');
    }

    foreach ($phongs as $phong) {
        $existingRating = DanhGiaSpace::where('dat_phong_id', $booking->id)
            ->where('user_id', Auth::id())
            ->where('phong_id', $phong->id)
            ->whereNotNull('rating')
            ->first();

        DanhGiaSpace::create([
            'phong_id' => $phong->id,
            'user_id' => Auth::id(),
            'dat_phong_id' => $booking->id,
            'rating' => $existingRating ? null : $request->rating,
            'noi_dung' => $request->noi_dung,
        ]);
    }

    return back()->with('success', 'Cảm ơn bạn đã đánh giá!');
}

}
