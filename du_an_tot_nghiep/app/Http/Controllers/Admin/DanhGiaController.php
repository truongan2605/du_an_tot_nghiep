<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGiaSpace;
use App\Models\Phong;
use App\Models\DatPhong; // cần dùng
use App\Models\LoaiPhong;
use Illuminate\Http\Request; // sửa từ Symfony sang Laravel
use App\Models\User; // nhớ import
use Illuminate\Support\Facades\Auth;

class DanhGiaController extends Controller
{
    // Danh sách phòng + trung bình đánh giá
public function index(Request $request)
{
    $query = Phong::query()
        ->withCount([
            'danhGias as tong_danh_gia' => function ($q) {
                $q->whereNull('parent_id');
            }
        ])
        ->withAvg('danhGias as rating_trung_binh', 'rating');

    // ⭐ Tìm theo loại phòng
    if ($request->filled('loai_phong')) {
        $query->where('loai_phong_id', $request->loai_phong);
    }

    // ⭐ Tìm theo tên phòng
    if ($request->filled('keyword')) {
        $query->where('name', 'LIKE', '%' . $request->keyword . '%');
    }

    $phongs = $query->paginate(10);

    // Lấy danh sách loại phòng cho filter
    $loaiPhongs = LoaiPhong::all();

    return view('admin.danhgia.index', compact('phongs', 'loaiPhongs'));
}





    // Chi tiết đánh giá 1 phòng
 public function show(Request $request, $id)
{
    $phong = Phong::findOrFail($id);

    // Query gốc
    $query = DanhGiaSpace::with(['user', 'replies'])
        ->where('phong_id', $id)
        ->whereNull('parent_id');  // chỉ lấy đánh giá gốc

    // ⭐ Lọc theo tên người dùng
    if ($request->filled('keyword')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->keyword . '%');
        });
    }

    // ⭐ Lọc từ ngày
    if ($request->filled('from_date')) {
        $query->whereDate('created_at', '>=', $request->from_date);
    }

    // ⭐ Lọc đến ngày
    if ($request->filled('to_date')) {
        $query->whereDate('created_at', '<=', $request->to_date);
    }

    // ⭐ Sắp xếp mới nhất
    $danhGias = $query->orderBy('created_at', 'desc')
        ->paginate(10);

    // Bỏ highlight đánh giá mới
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

public function reply(Request $request, $id)
{
    $request->validate([
        'noi_dung' => 'required|string'
    ]);

    $parent = DanhGiaSpace::findOrFail($id);

    DanhGiaSpace::create([
        'phong_id'      => $parent->phong_id,
        'user_id'       => Auth::id(),
        'dat_phong_id'  => $parent->dat_phong_id,   // ⭐ KHÔNG ĐỂ NULL NỮA
        'rating'        => null,
        'noi_dung'      => $request->noi_dung,
        'parent_id'     => $parent->id,
        'status'        => 1
    ]);

    return back()->with('success', 'Đã trả lời bình luận.');
}
public function destroy($id)
{
    $dg = DanhGiaSpace::findOrFail($id);

    $dg->delete();

    return back()->with('success', 'Đã xóa đánh giá.');
}





}
