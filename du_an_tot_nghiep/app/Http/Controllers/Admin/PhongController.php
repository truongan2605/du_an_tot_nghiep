<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tang;
use App\Models\Phong;
use App\Models\TienNghi;
use App\Models\LoaiPhong;
use App\Models\PhongImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimKiemPhongRequest;
use Illuminate\Support\Facades\Storage;

class PhongController extends Controller
{
    public function index()
    {
        $phongs = Phong::with(['loaiPhong','tang','images'])->get();
        return view('admin.phong.index', compact('phongs'));
    }

    public function create()
{
    $loaiPhongs = LoaiPhong::all();
    $tangs = Tang::all();
    $tienNghis = TienNghi::where('active', true)->get();

    return view('admin.phong.create', compact('loaiPhongs','tangs','tienNghis'));
}


    public function store(Request $request)
{
    $request->validate([
        'ma_phong' => 'required|unique:phong,ma_phong',
        'loai_phong_id' => 'required|integer',
        'tang_id' => 'required|integer',
        'suc_chua' => 'required|integer|min:1',
        'so_giuong' => 'required|integer|min:1',
        'gia_mac_dinh' => 'required|numeric|min:0',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        'tien_nghi' => 'nullable|array',
        'tien_nghi.*' => 'exists:tien_nghi,id'
    ]);

    DB::beginTransaction();
    try {
        $data = $request->only([
            'ma_phong','loai_phong_id','tang_id',
            'suc_chua','so_giuong','gia_mac_dinh','trang_thai'
        ]);

        $phong = Phong::create($data);

        // lưu nhiều ảnh (nếu có)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('uploads/phong', 'public');
                $phong->images()->create(['image_path' => $path]);
            }
        }

        // lưu tiện nghi (nếu có)
        if ($request->has('tien_nghi')) {
            $phong->tienNghis()->sync($request->tien_nghi);
        }

        DB::commit();
        return redirect()->route('admin.phong.index')
                         ->with('success','Thêm phòng thành công');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()
                     ->withErrors(['error' => 'Lỗi lưu phòng: '.$e->getMessage()]);
    }
}

    public function edit(Phong $phong)
{
    $loaiPhongs = LoaiPhong::all();
    $tangs = Tang::all();
    $tienNghis = TienNghi::where('active', true)->get();
    $phong->load(['images','tienNghis']);

    return view('admin.phong.edit', compact('phong','loaiPhongs','tangs','tienNghis'));
}

 public function update(Request $request, Phong $phong)
{
    $request->validate([
        'ma_phong' => 'required|unique:phong,ma_phong,' . $phong->id,
        'loai_phong_id' => 'required|integer',
        'tang_id' => 'required|integer',
        'suc_chua' => 'required|integer|min:1',
        'so_giuong' => 'required|integer|min:1',
        'gia_mac_dinh' => 'required|numeric|min:0',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        'tien_nghi' => 'nullable|array',
        'tien_nghi.*' => 'exists:tien_nghi,id'
    ]);

    DB::beginTransaction();
    try {
        $data = $request->only([
            'ma_phong','loai_phong_id','tang_id',
            'suc_chua','so_giuong','gia_mac_dinh','trang_thai'
        ]);

        $phong->update($data);

        // Nếu có ảnh mới: thêm vào bảng phong_images (không xóa ảnh cũ)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('uploads/phong', 'public');
                $phong->images()->create(['image_path' => $path]);
            }
        }

        // Cập nhật tiện nghi (ghi đè các tiện nghi cũ)
        if ($request->has('tien_nghi')) {
            $phong->tienNghis()->sync($request->tien_nghi);
        } else {
            $phong->tienNghis()->sync([]); // nếu không chọn gì thì clear hết
        }

        DB::commit();
        return redirect()->route('admin.phong.index')->with('success','Cập nhật phòng thành công');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()->withErrors(['error' => 'Lỗi cập nhật: '.$e->getMessage()]);
    }
}

 public function show($id)
{
    $phong = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis'])->findOrFail($id);
    return view('admin.phong.show', compact('phong'));
}



    public function destroy(Phong $phong)
    {
        // xóa file ảnh trên disk + record ảnh
        foreach ($phong->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
            $img->delete();
        }

        $phong->delete();
        return redirect()->route('admin.phong.index')->with('success','Xóa phòng thành công');
    }

    // Xóa 1 ảnh riêng biệt (không xóa phòng)
    public function destroyImage(PhongImage $image)
    {
        // xóa file nếu tồn tại
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        // trở về trang trước (hoặc trả JSON nếu ajax)
        return back()->with('success','Xóa ảnh thành công');
    }
public function timKiem(\App\Http\Requests\TimKiemPhongRequest $request)
{
    $tuNgay       = $request->input('tu_ngay');
    $denNgay      = $request->input('den_ngay');
    $soKhach      = $request->input('so_khach');
    $loaiPhongId  = $request->input('loai_phong_id');         // có thể rỗng
    $loaiPhongTxt = trim((string) $request->input('loai_phong_text')); // <- TEXT người dùng gõ
    $giaTu        = $request->input('gia_tu');
    $giaDen       = $request->input('gia_den');
    $sapXep       = $request->input('sap_xep', 'gia_tang');
    $perPage      = (int) $request->input('per_page', 15);

    $query = \App\Models\Phong::query()
        ->with('loaiPhong:id,ten_loai') // đổi đúng cột của bạn
        ->select(['id','ten','mo_ta','so_nguoi_toi_da','loai_phong_id','gia_theo_dem','created_at'])
        ->trongKhoangThoiGian($tuNgay, $denNgay)
        // lọc theo số khách, id loại phòng, giá...
        ->phuHopBoLoc($soKhach, $loaiPhongId, $giaTu, $giaDen)
        // 🔥 luôn lọc theo text người dùng gõ (nếu có)
        ->tuKhoa($loaiPhongTxt);

    switch ($sapXep) {
        case 'gia_giam': $query->orderByDesc('gia_theo_dem'); break;
        case 'moi_nhat': $query->orderByDesc('id'); break;
        case 'cu_nhat' : $query->orderBy('id'); break;
        default        : $query->orderBy('gia_theo_dem'); break;
    }

    $paginator = $query->paginate($perPage);

    $items = collect($paginator->items())->map(function ($p) {
        return [
            'id'              => $p->id,
            'ten'             => $p->ten,
            'mo_ta'           => $p->mo_ta,
            'so_nguoi_toi_da' => $p->so_nguoi_toi_da,
            'gia_theo_dem'    => $p->gia_theo_dem,
            'loai_phong'      => $p->relationLoaded('loaiPhong') && $p->loaiPhong
                                  ? ['id' => $p->loai_phong_id, 'ten' => $p->loaiPhong->ten_loai]
                                  : null,
            'created_at'      => optional($p->created_at)->toDateTimeString(),
        ];
    });

    return response()->json([
        'success' => true,
        'data'    => $items,
        'meta'    => [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
        ],
    ]);
}


}
