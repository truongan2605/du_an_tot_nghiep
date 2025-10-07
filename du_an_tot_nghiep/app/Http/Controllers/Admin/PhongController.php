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
        $phongs = Phong::with(['loaiPhong', 'tang', 'images'])->orderBy('id', 'desc')->get();
        return view('admin.phong.index', compact('phongs'));
    }

    public function create()
    {
        $loaiPhongs = LoaiPhong::with('tienNghis')->get();
        $tangs = Tang::all();
        $tienNghis = TienNghi::where('active', true)->get();

        return view('admin.phong.create', compact('loaiPhongs', 'tangs', 'tienNghis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ma_phong' => 'required|unique:phong,ma_phong',
            'loai_phong_id' => 'required|exists:loai_phong,id',
            'tang_id' => 'required|exists:tang,id',
            'suc_chua' => 'required|integer|min:1',
            'so_giuong' => 'required|integer|min:1',
            'gia_mac_dinh' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'integer|exists:tien_nghi,id',
            'trang_thai' => 'nullable|in:trong,dang_o,bao_tri,khong_su_dung'
        ]);

        DB::beginTransaction();
        try {
            $loaiPhong = LoaiPhong::findOrFail($request->loai_phong_id);
            $basePrice = (float) ($loaiPhong->gia_mac_dinh ?? 0);

            $selectedAmenityIds = $request->input('tien_nghi', []);

            $amenitiesSum = 0;
            if (!empty($selectedAmenityIds)) {
                $amenitiesSum = (float) TienNghi::whereIn('id', $selectedAmenityIds)->sum('gia');
            }

            if ($request->filled('gia_mac_dinh') && $request->input('gia_mac_dinh') > 0) {
                $finalPrice = (float) $request->input('gia_mac_dinh');
            } else {
                $finalPrice = $basePrice + $amenitiesSum;
            }

            $data = $request->only([
                'ma_phong',
                'loai_phong_id',
                'tang_id',
                'suc_chua',
                'so_giuong'
            ]);
            $data['gia_mac_dinh'] = $finalPrice;
            $data['trang_thai'] = $request->input('trang_thai', 'khong_su_dung');

            $phong = Phong::create($data);

            // lưu ảnh nếu có
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
            }

            if (!empty($selectedAmenityIds)) {
                $phong->tienNghis()->sync($selectedAmenityIds);
            }

            DB::commit();
            return redirect()->route('admin.phong.index')
                ->with('success', 'Thêm phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()
                ->withErrors(['error' => 'Lỗi lưu phòng: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $phong = Phong::with(['images', 'tienNghis', 'loaiPhong', 'tang'])->findOrFail($id);
        $loaiPhongs = LoaiPhong::with('tienNghis')->get();
        $tangs = Tang::all();
        $tienNghis = TienNghi::where('active', true)->get();

        return view('admin.phong.edit', compact('phong', 'loaiPhongs', 'tangs', 'tienNghis'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ma_phong' => 'required|unique:phong,ma_phong,' . $id,
            'name' => 'nullable|string|max:255',
            'mo_ta' => 'nullable|string',
            'loai_phong_id' => 'required|exists:loai_phong,id',
            'tang_id' => 'required|exists:tang,id',
            'suc_chua' => 'required|integer|min:1',
            'so_giuong' => 'required|integer|min:1',
            'gia_mac_dinh' => 'nullable|numeric|min:0',
            'override_price' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'integer|exists:tien_nghi,id',
            'trang_thai' => 'nullable|in:khong_su_dung,trong,dang_o,bao_tri',
        ]);

        DB::beginTransaction();
        try {
            $phong = Phong::findOrFail($id);

            $selectedLoaiId = (int) $request->input('loai_phong_id');
            $selectedLoai = LoaiPhong::find($selectedLoaiId);

            if (!$selectedLoai) {
                return back()->withInput()->withErrors(['loai_phong_id' => 'Loại phòng không tồn tại.']);
            }

            $requestedStatus = $request->input('trang_thai', $phong->trang_thai);

            if ($selectedLoai->active == false) {
                if ($requestedStatus !== $phong->trang_thai) {
                    return back()->withInput()->withErrors(['trang_thai' => 'Không được thay đổi trạng thái phòng khi loại phòng đang bị vô hiệu hoá.']);
                }
            }

            $basePrice = (float) ($selectedLoai->gia_mac_dinh ?? 0);

            $selectedAmenityIds = $request->input('tien_nghi', []);
            $amenitiesSum = 0;
            if (!empty($selectedAmenityIds)) {
                $amenitiesSum = (float) TienNghi::whereIn('id', $selectedAmenityIds)->sum('gia');
            }

            $override = (bool) $request->input('override_price', false);
            if ($override && $request->filled('gia_mac_dinh') && $request->input('gia_mac_dinh') >= 0) {
                $finalPrice = (float) $request->input('gia_mac_dinh');
            } else {
                $finalPrice = $basePrice + $amenitiesSum;
            }

            $data = $request->only([
                'ma_phong',
                'name',
                'mo_ta',
                'loai_phong_id',
                'tang_id',
                'suc_chua',
                'so_giuong',
            ]);

            $data['trang_thai'] = $requestedStatus;
            $data['gia_mac_dinh'] = $finalPrice;

            $phong->update($data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
            }

            if (!empty($selectedAmenityIds)) {
                $phong->tienNghis()->sync($selectedAmenityIds);
            } else {
                $phong->tienNghis()->detach();
            }

            DB::commit();
            return redirect()->route('admin.phong.index')->with('success', 'Cập nhật phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi cập nhật phòng: ' . $e->getMessage()]);
        }
    }


    public function show($id)
    {
        $phong = Phong::with(['loaiPhong.tienNghis', 'tienNghis'])->findOrFail($id);

        $tienNghiLoaiPhong = $phong->loaiPhong->tienNghis ?? collect();

        $tienNghiPhong = $phong->tienNghis ?? collect();

        return view('admin.phong.show', compact('phong', 'tienNghiLoaiPhong', 'tienNghiPhong'));
    }

    public function destroy(Phong $phong)
    {
        DB::beginTransaction();
        try {
            $phong->tienNghis()->detach();

            foreach ($phong->images as $img) {
                if (Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                $img->delete();
            }

            $phong->delete();

            DB::commit();
            return redirect()->route('admin.phong.index')
                ->with('success', 'Xóa phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi xóa: ' . $e->getMessage()]);
        }
    }

    public function destroyImage(PhongImage $image)
    {
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return back()->with('success', 'Xóa ảnh thành công');
    }
}
