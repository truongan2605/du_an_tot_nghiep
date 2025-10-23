<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BedType;
use App\Models\LoaiPhong;
use App\Models\Phong;
use App\Models\PhongImage;
use App\Models\Tang;
use App\Models\TienNghi;
use App\Models\VatDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhongController extends Controller
{
    public function index(Request $request)
    {
        $query = Phong::with(['loaiPhong', 'tang', 'images'])->orderBy('id', 'desc');

        // --- Bộ lọc ---
        if ($request->filled('ma_phong')) {
            $query->where('ma_phong', 'like', '%' . $request->ma_phong . '%');
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('loai_phong_id')) {
            $query->where('loai_phong_id', $request->loai_phong_id);
        }

        if ($request->filled('tang_id')) {
            $query->where('tang_id', $request->tang_id);
        }

        // Lấy dữ liệu
        $phongs = $query->get();

        // Dữ liệu cho dropdown
        $loaiPhongs = \App\Models\LoaiPhong::all();
        $tangs = \App\Models\Tang::all();

        return view('admin.phong.index', compact('phongs', 'loaiPhongs', 'tangs'));
    }


    public function create()
    {
        $loaiPhongs = LoaiPhong::with('tienNghis', 'bedTypes')->get();
        $tangs = Tang::all();
        $tienNghis = TienNghi::where('active', true)->get();
        $vatDungs = VatDung::where('active', true)->get();
        $bedTypes = BedType::orderBy('name')->get();

        return view('admin.phong.create', compact('loaiPhongs', 'tangs', 'tienNghis', 'vatDungs', 'bedTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ma_phong' => 'required|unique:phong,ma_phong',
            'name' => 'nullable|string|max:255',
            'mo_ta' => 'nullable|string',
            'loai_phong_id' => 'required|exists:loai_phong,id',
            'tang_id' => 'required|exists:tang,id',
            'suc_chua' => 'nullable|integer|min:1',
            'so_giuong' => 'nullable|integer|min:1',
            'gia_mac_dinh' => 'nullable|numeric|min:0',
            'override_price' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'integer|exists:tien_nghi,id',
            'vat_dungs' => 'nullable|array',
            'vat_dungs.*' => 'exists:vat_dungs,id',
            'bed_types' => 'nullable|array',
            'trang_thai' => 'nullable|in:trong,dang_o,bao_tri,khong_su_dung'
        ]);

        DB::beginTransaction();
        try {
            $selectedLoai = LoaiPhong::findOrFail($request->loai_phong_id);
            $override = (bool) $request->input('override_price', false);
            $inputBase = $override && $request->filled('gia_mac_dinh') && $request->input('gia_mac_dinh') >= 0
                ? (float) $request->input('gia_mac_dinh')
                : (float) ($selectedLoai->gia_mac_dinh ?? 0);

            $basePrice = (float) ($selectedLoai->gia_mac_dinh ?? 0);

            $selectedAmenityIds = $request->input('tien_nghi', []);
            $amenitiesSum = !empty($selectedAmenityIds)
                ? (float) TienNghi::whereIn('id', $selectedAmenityIds)->sum('gia')
                : 0.0;

            $bedTotal = 0.0;
            $roomBedData = $request->input('bed_types', null);

            if (is_array($roomBedData) && count($roomBedData) > 0) {
                foreach ($roomBedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int) $vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float) $vals['price'] : null;
                    if ($price === null) {
                        $bt = BedType::find($bedTypeId);
                        $price = $bt ? (float) ($bt->price ?? 0) : 0;
                    }
                    $bedTotal += $qty * $price;
                }
            } else {
                $selectedLoai->load('bedTypes');
                foreach ($selectedLoai->bedTypes as $bt) {
                    $qty = (int) ($bt->pivot->quantity ?? 0);
                    if ($qty <= 0) continue;
                    $pricePer = $bt->pivot->price !== null ? (float) $bt->pivot->price : (float) ($bt->price ?? 0);
                    $bedTotal += $qty * $pricePer;
                }
            }

            $inputBase = $request->filled('gia_mac_dinh') && $request->input('gia_mac_dinh') >= 0
                ? (float) $request->input('gia_mac_dinh')
                : $basePrice;

            $finalTotal = $inputBase + $amenitiesSum + $bedTotal;

            $data = [
                'ma_phong' => $request->input('ma_phong'),
                'name' => $request->input('name'),
                'mo_ta' => $request->input('mo_ta'),
                'loai_phong_id' => $selectedLoai->id,
                'tang_id' => $request->input('tang_id'),
                'suc_chua' => (int) $selectedLoai->suc_chua,
                'so_giuong' => (int) $selectedLoai->so_giuong,
                'gia_mac_dinh' => $inputBase,
                'gia_cuoi_cung' => $finalTotal,
                'trang_thai' => $request->input('trang_thai', 'khong_su_dung'),
            ];

            $phong = Phong::create($data);

            if (is_array($roomBedData) && count($roomBedData) > 0) {
                $attach = [];
                foreach ($roomBedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int) $vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float) $vals['price'] : null;
                    $attach[$bedTypeId] = ['quantity' => $qty, 'price' => $price];
                }
                if (!empty($attach)) {
                    $phong->bedTypes()->sync($attach);
                }
            } else {
                $attach = [];
                $selectedLoai->load('bedTypes');
                foreach ($selectedLoai->bedTypes as $bt) {
                    $attach[$bt->id] = [
                        'quantity' => $bt->pivot->quantity ?? 0,
                        'price' => $bt->pivot->price ?? null,
                    ];
                }
                if (!empty($attach)) {
                    $phong->bedTypes()->sync($attach);
                }
            }

            // images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
            }

            // amenities
            if (!empty($selectedAmenityIds)) {
                $phong->tienNghis()->sync($selectedAmenityIds);
            } else {
                $phong->tienNghis()->detach();
            }

            $phong->loadMissing(['loaiPhong.tienNghis', 'tienNghis', 'bedTypes']);
            $phong->recalcAndSave(true);

            DB::commit();
            return redirect()->route('admin.phong.index')->with('success', 'Thêm phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi lưu phòng: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $phong = Phong::with(['images', 'tienNghis', 'loaiPhong', 'tang', 'bedTypes'])->findOrFail($id);
        $loaiPhongs = LoaiPhong::with('tienNghis', 'bedTypes')->get();
        $tangs = Tang::all();
        $tienNghis = TienNghi::where('active', true)->get();
        $bedTypes = BedType::orderBy('name')->get();

        return view('admin.phong.edit', compact('phong', 'loaiPhongs', 'tangs', 'tienNghis', 'bedTypes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ma_phong' => 'required|unique:phong,ma_phong,' . $id,
            'name' => 'nullable|string|max:255',
            'mo_ta' => 'nullable|string',
            'loai_phong_id' => 'required|exists:loai_phong,id',
            'tang_id' => 'required|exists:tang,id',
            'suc_chua' => 'nullable|integer|min:1',
            'so_giuong' => 'nullable|integer|min:1',
            'gia_mac_dinh' => 'nullable|numeric|min:0',
            'override_price' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'integer|exists:tien_nghi,id',
            'vat_dungs' => 'nullable|array',
            'vat_dungs.*' => 'exists:vat_dungs,id',
            'bed_types' => 'nullable|array',
            'trang_thai' => 'nullable|in:khong_su_dung,trong,dang_o,bao_tri',
        ]);

        DB::beginTransaction();
        try {
            $phong = Phong::findOrFail($id);
            $selectedLoai = LoaiPhong::findOrFail((int)$request->input('loai_phong_id'));

            $requestedStatus = $request->input('trang_thai', $phong->trang_thai);
            if ($selectedLoai->active == false && $requestedStatus !== $phong->trang_thai) {
                return back()->withInput()->withErrors(['trang_thai' => 'Không được thay đổi trạng thái phòng khi loại phòng đang bị vô hiệu hoá.']);
            }

            $basePrice = (float) ($selectedLoai->gia_mac_dinh ?? 0);
            $selectedAmenityIds = $request->input('tien_nghi', []);
            $amenitiesSum = !empty($selectedAmenityIds)
                ? (float) TienNghi::whereIn('id', $selectedAmenityIds)->sum('gia')
                : 0.0;

            $bedTotal = 0.0;
            $roomBedData = $request->input('bed_types', null);

            if (is_array($roomBedData) && count($roomBedData) > 0) {
                foreach ($roomBedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float)$vals['price'] : null;
                    if ($price === null) {
                        $bt = BedType::find($bedTypeId);
                        $price = $bt ? (float) ($bt->price ?? 0) : 0;
                    }
                    $bedTotal += $qty * $price;
                }
            } else {
                $selectedLoai->load('bedTypes');
                foreach ($selectedLoai->bedTypes as $bt) {
                    $qty = (int) ($bt->pivot->quantity ?? 0);
                    if ($qty <= 0) continue;
                    $pricePer = $bt->pivot->price !== null ? (float) $bt->pivot->price : (float) ($bt->price ?? 0);
                    $bedTotal += $qty * $pricePer;
                }
            }

            $override = (bool) $request->input('override_price', false);
            $inputBase = $override && $request->filled('gia_mac_dinh') && $request->input('gia_mac_dinh') >= 0
                ? (float) $request->input('gia_mac_dinh')
                : $basePrice;

            $finalTotal = $inputBase + $amenitiesSum + $bedTotal;

            $data = [
                'ma_phong' => $request->input('ma_phong'),
                'name' => $request->input('name'),
                'mo_ta' => $request->input('mo_ta'),
                'loai_phong_id' => $selectedLoai->id,
                'tang_id' => $request->input('tang_id'),
                'suc_chua' => (int)$selectedLoai->suc_chua,
                'so_giuong' => (int)$selectedLoai->so_giuong,
                'trang_thai' => $requestedStatus,
                'gia_mac_dinh' => $inputBase,
                'gia_cuoi_cung' => $finalTotal,
            ];

            $phong->update($data);

            if (is_array($roomBedData) && count($roomBedData) > 0) {
                $attach = [];
                foreach ($roomBedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float)$vals['price'] : null;
                    $attach[$bedTypeId] = ['quantity' => $qty, 'price' => $price];
                }
                if (!empty($attach)) {
                    $phong->bedTypes()->sync($attach);
                } else {
                    $phong->bedTypes()->detach();
                    $selectedLoai->load('bedTypes');
                    $attach = [];
                    foreach ($selectedLoai->bedTypes as $bt) {
                        $attach[$bt->id] = ['quantity' => $bt->pivot->quantity ?? 0, 'price' => $bt->pivot->price ?? null];
                    }
                    if (!empty($attach)) $phong->bedTypes()->sync($attach);
                }
            } else {
                $selectedLoai->load('bedTypes');
                $attach = [];
                foreach ($selectedLoai->bedTypes as $bt) {
                    $attach[$bt->id] = ['quantity' => $bt->pivot->quantity ?? 0, 'price' => $bt->pivot->price ?? null];
                }
                if (!empty($attach)) $phong->bedTypes()->sync($attach);
            }

            // images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('uploads/phong', 'public');
                    $phong->images()->create(['image_path' => $path]);
                }
            }

            // amenities sync
            if (!empty($selectedAmenityIds)) {
                $phong->tienNghis()->sync($selectedAmenityIds);
            } else {
                $phong->tienNghis()->detach();
            }

            $phong->loadMissing(['loaiPhong.tienNghis', 'tienNghis', 'bedTypes']);
            $phong->recalcAndSave(true);

            DB::commit();
            return redirect()->route('admin.phong.index')->with('success', 'Cập nhật phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi cập nhật phòng: ' . $e->getMessage()]);
        }
    }

    //     public function show($id)
    //     {
    //         $phong = Phong::with(['loaiPhong.tienNghis', 'tienNghis','vatDungs','bedTypes'])->findOrFail($id);

    //         $tienNghiLoaiPhong = $phong->loaiPhong->tienNghis ?? collect();
    //         $tienNghiPhong = $phong->tienNghis ?? collect();
    //         $vatDungLoaiPhong = $phong->loaiPhong->vatDungs ?? collect();
    // $vatDungPhong = $phong->vatDungs ?? collect();


    //         return view('admin.phong.show', compact('phong', 'tienNghiLoaiPhong', 'tienNghiPhong'));
    //     }
    public function show($id)
    {
        $phong = Phong::with(['loaiPhong.tienNghis', 'tienNghis', 'vatDungs', 'loaiPhong.vatDungs'])->findOrFail($id);

        // giống logic tiện nghi
        $tienNghiLoaiPhong = $phong->loaiPhong?->tienNghis ?? collect();
        $tienNghiPhong = $phong->tienNghis ?? collect();

        // phần mới cho vật dụng
        $vatDungLoaiPhong = $phong->loaiPhong?->vatDungs()->where('active', 1)->get() ?? collect();
        $vatDungPhong = $phong->vatDungs()->where('active', 1)->get() ?? collect();

        return view('admin.phong.show', compact(
            'phong',
            'tienNghiLoaiPhong',
            'tienNghiPhong',
            'vatDungLoaiPhong',
            'vatDungPhong'
        ));
    }

    public function destroy(Phong $phong)
    {
        DB::beginTransaction();
        try {
            $hasBookings = $phong->phongDaDats()->exists();
            if ($hasBookings) {
                return back()->withErrors(['error' => 'Không thể xóa phòng vì đã có booking liên quan.']);
            }

            $phong->tienNghis()->detach();
            $phong->bedTypes()->detach();

            $phong->wishlists()->delete();

            foreach ($phong->images as $img) {
                if (Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                $img->delete();
            }

            $phong->delete();

            if ($phong->loai_phong_id) {
                LoaiPhong::refreshSoLuongThucTe($phong->loai_phong_id);
            }

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
