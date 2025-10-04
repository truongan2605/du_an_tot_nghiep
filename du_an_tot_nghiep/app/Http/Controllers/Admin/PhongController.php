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

   public function edit($id)
{
    $phong = Phong::with(['images','tienNghis','loaiPhong','tang'])->findOrFail($id);
    $loaiPhongs = LoaiPhong::all();
    $tangs = Tang::all();
    $tienNghis = TienNghi::where('active', true)->get();

    return view('admin.phong.edit', compact('phong','loaiPhongs','tangs','tienNghis'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'ma_phong' => 'required|unique:phong,ma_phong,'.$id,
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
        $phong = Phong::findOrFail($id);

        $data = $request->only([
            'ma_phong','loai_phong_id','tang_id',
            'suc_chua','so_giuong','gia_mac_dinh','trang_thai'
        ]);

        $phong->update($data);

        // upload ảnh mới (nếu có)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('uploads/phong', 'public');
                $phong->images()->create(['image_path' => $path]);
            }
        }

        // update tiện nghi
        if ($request->has('tien_nghi')) {
            $phong->tienNghis()->sync($request->tien_nghi);
        }

        DB::commit();
        return redirect()->route('admin.phong.index')
                         ->with('success','Cập nhật phòng thành công');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()
                     ->withErrors(['error' => 'Lỗi cập nhật phòng: '.$e->getMessage()]);
    }
}


public function show($id)
{
    $phong = Phong::with(['loaiPhong.tiennghis', 'tiennghis'])->findOrFail($id);

    // Tiện nghi mặc định từ loại phòng
    $tienNghiLoaiPhong = $phong->loaiPhong->tiennghis ?? collect();

    // Tiện nghi riêng của phòng
    $tienNghiPhong = $phong->tiennghis ?? collect();

    return view('admin.phong.show', compact('phong', 'tienNghiLoaiPhong', 'tienNghiPhong'));
}



   public function destroy(Phong $phong)
{
    DB::beginTransaction();
    try {
        // Xóa quan hệ tiện nghi trước
        $phong->tienNghis()->detach();

        // Xóa ảnh
        foreach ($phong->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
            $img->delete();
        }

        // Xóa phòng
        $phong->delete();

        DB::commit();
        return redirect()->route('admin.phong.index')
                         ->with('success','Xóa phòng thành công');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Lỗi xóa: '.$e->getMessage()]);
    }
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
}
