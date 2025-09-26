<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\PhongImage;
use App\Models\LoaiPhong;
use App\Models\Tang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return view('admin.phong.create', compact('loaiPhongs','tangs'));
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
            $data = $request->only(['ma_phong','loai_phong_id','tang_id','suc_chua','so_giuong','gia_mac_dinh','trang_thai']);
            $phong = Phong::create($data);

            // lưu nhiều ảnh (nếu có)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
            }

            DB::commit();
            return redirect()->route('admin.phong.index')->with('success','Thêm phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi lưu phòng: '.$e->getMessage()]);
        }
    }

    public function edit(Phong $phong)
    {
        $loaiPhongs = LoaiPhong::all();
        $tangs = Tang::all();
        $phong->load('images');
        return view('admin.phong.edit', compact('phong','loaiPhongs','tangs'));
    }

    public function update(Request $request, Phong $phong)
    {
        $request->validate([
            'ma_phong' => 'required|unique:phong,ma_phong,'.$phong->id,
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
            $data = $request->only(['ma_phong','loai_phong_id','tang_id','suc_chua','so_giuong','gia_mac_dinh','trang_thai']);
            $phong->update($data);

            // Nếu có ảnh mới: **thêm** vào bảng phong_images (không xóa ảnh cũ)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
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
    $phong = Phong::with(['loaiPhong', 'tang', 'images'])->findOrFail($id);
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
}
