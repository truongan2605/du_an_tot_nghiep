<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSpace;
use App\Models\Phong;
use App\Models\DatPhong; // cần dùng
use Illuminate\Http\Request; // sửa từ Symfony sang Laravel
use App\Models\User; // nhớ import
use Illuminate\Support\Facades\Auth;

class DanhGiaController extends Controller
{
    // Danh sách phòng + trung bình đánh giá
    public function index()
    {
        $phongs = Phong::withCount([
            'danhGias as tong_danh_gia',
            'danhGias as danh_gia_moi_count' => function ($q) {
                $q->where('is_new', 1);
            }
        ])
        ->withAvg('danhGias as rating_trung_binh', 'rating')
        ->paginate(10);

        return view('admin.danhgia.index', compact('phongs'));
    }

    // Chi tiết đánh giá 1 phòng
    public function show($id)
    {
        $phong = Phong::findOrFail($id);

        $danhGias = DanhGiaSpace::where('phong_id', $id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Đánh dấu đã xem
        DanhGiaSpace::where('phong_id', $id)->update(['is_new' => 0]);

        return view('admin.danhgia.show', compact('phong', 'danhGias'));
    }

  public function store(Request $request, $phongId)
{
    $user = Auth::user();

    // 1. Lấy đơn đặt phòng đã checkout
    $datPhong = \App\Models\DatPhong::where('phong_id', $phongId)
        ->where('user_id', $user->id)
        ->where('trang_thai', 'da_checkout')
        ->first();

    if (!$datPhong) {
        return back()->with('error', 'Bạn chỉ có thể đánh giá sau khi đã trả phòng (check-out).');
    }

    // 2. Kiểm tra user đã đánh giá đơn đó chưa
    $daDanhGia = \App\Models\DanhGiaSpace::where('dat_phong_id', $datPhong->id)
        ->where('user_id', $user->id)
        ->exists();

    if ($daDanhGia) {
        return back()->with('error', 'Bạn đã đánh giá cho lần đặt phòng này rồi.');
    }

    // 3. Validate dữ liệu
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'noi_dung' => 'required|string',
    ]);

    // 4. Lưu đánh giá
    DanhGiaSpace::create([
        'phong_id' => $phongId,
        'user_id' => $user->id,
        'dat_phong_id' => $datPhong->id,
        'rating' => $request->rating,
        'noi_dung' => $request->noi_dung,
    ]);

    return back()->with('success', 'Cảm ơn bạn đã đánh giá!');
}

    // Toggle ẩn/hiện đánh giá
    public function toggleStatus($id)
    {
        $dg = DanhGiaSpace::findOrFail($id);
        $dg->status = !$dg->status;
        $dg->save();

        return back()->with('success', 'Đã cập nhật trạng thái đánh giá');
    }
}
