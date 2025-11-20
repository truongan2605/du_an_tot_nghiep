<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use App\Models\VatDung;
use App\Models\BedType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoaiPhongController extends Controller
{
    public function index(Request $request)
    {
        $query = LoaiPhong::with('tienNghis')
            ->withCount([
                'phongs',
                'phongs as occupied_count' => function ($q) {
                    $q->where('trang_thai', 'dang_o');
                },
            ]);

        // Lọc theo tên
        if ($request->filled('ten')) {
            $query->where('ten', 'like', '%' . $request->ten . '%');
        }

        // Lọc theo tiện nghi
        if ($request->filled('tien_nghi_ids')) {
            $tien_nghi_ids = (array) $request->tien_nghi_ids;

            foreach ($tien_nghi_ids as $tnId) {
                $query->whereHas('tienNghis', function ($q) use ($tnId) {
                    $q->where('tien_nghi.id', $tnId);
                });
            }
        }

        $loaiPhongs = $query->orderByDesc('id')->get();
        $dsTienNghis = TienNghi::all();

        return view('admin.loai_phong.index', compact('loaiPhongs', 'dsTienNghis'));
    }


    public function create()
    {
        $tienNghis = TienNghi::where('active', true)->get();
        $vatDungs = VatDung::where('active', true)->where('loai', VatDung::LOAI_DO_DUNG)->get();
        $bedTypes = BedType::orderBy('name')->get();

        return view('admin.loai_phong.create', compact('tienNghis', 'bedTypes', 'vatDungs'));
    }

    public function store(Request $request)
    {
        // ✨ Convert giá từ "10.000.000" → "10000000" trước khi validate
        $request->merge([
            'gia_mac_dinh' => str_replace('.', '', $request->gia_mac_dinh)
        ]);

        $request->validate([
            'ma' => 'required|string|max:50|unique:loai_phong,ma',
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'suc_chua' => 'nullable|integer|min:0',
            'so_giuong' => 'nullable|integer|min:0',
            'gia_mac_dinh' => 'required|numeric|min:0',
            'so_luong_thuc_te' => 'nullable|integer|min:0',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'exists:tien_nghi,id',
            'tien_nghi_ids' => 'nullable|array',
            'tien_nghi_ids.*' => 'exists:tien_nghi,id',
            'vat_dungs' => 'nullable|array',
            'vat_dungs.*' => 'exists:vat_dungs,id',
            'vat_dung_ids' => 'nullable|array',
            'vat_dung_ids.*' => 'exists:vat_dungs,id',
            'bed_types' => 'nullable|array',
            'bed_types.*.quantity' => 'nullable|integer|min:0',
            'bed_types.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['ma', 'ten', 'mo_ta', 'gia_mac_dinh']);

            // Chuyển "5.000.000" thành 5000000
            $data['gia_mac_dinh'] = (int) str_replace('.', '', $data['gia_mac_dinh']);

            $data['so_luong_thuc_te'] = $request->input('so_luong_thuc_te', 0);

            $data['suc_chua'] = (int) $request->input('suc_chua', 0);
            $data['so_giuong'] = (int) $request->input('so_giuong', 0);

            $loaiPhong = LoaiPhong::create($data);

            $tienNghiInput = $request->input('tien_nghi', $request->input('tien_nghi_ids', []));
            if (!empty($tienNghiInput)) {
                $loaiPhong->tienNghis()->sync($tienNghiInput);
            }

            $rawVatDungs = $request->input('vat_dungs', $request->input('vat_dung_ids', []));
            if (!empty($rawVatDungs)) {
                $ids = VatDung::whereIn('id', (array)$rawVatDungs)
                    ->where('loai', VatDung::LOAI_DO_DUNG)
                    ->pluck('id')
                    ->toArray();
                $loaiPhong->vatDungs()->sync($ids);
            }
            if (!empty($ids)) {
                $phongs = \App\Models\Phong::where('loai_phong_id', $loaiPhong->id)->get();
                foreach ($phongs as $p) {
                    foreach ($ids as $vdId) {
                        $exists = DB::table('phong_vat_dung')
                            ->where('phong_id', $p->id)
                            ->where('vat_dung_id', $vdId)
                            ->exists();
                        if (! $exists) {
                            DB::table('phong_vat_dung')->insert([
                                'phong_id' => $p->id,
                                'vat_dung_id' => $vdId,
                                'so_luong' => 0,
                                'gia_override' => null,
                                'tracked_instances' => (bool) \App\Models\VatDung::find($vdId)->tracked_instances,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }


            $bedData = $request->input('bed_types', null);

            if ($request->has('bed_types')) {
                $hasPositive = false;
                foreach ($bedData as $btId => $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty > 0) {
                        $hasPositive = true;
                        break;
                    }
                }
                if (! $hasPositive) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['bed_types' => 'Bạn phải chọn ít nhất 1 loại giường với số lượng > 0.']);
                }

                $sync = [];
                foreach ($bedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float)$vals['price'] : null;
                    $sync[$bedTypeId] = ['quantity' => $qty, 'price' => $price];
                }

                $loaiPhong->bedTypes()->sync($sync);

                $totalCapacity = 0;
                $totalBeds = 0;
                $bedModels = $loaiPhong->bedTypes()->get();
                foreach ($bedModels as $b) {
                    $qty = (int) ($b->pivot->quantity ?? 0);
                    $cap = (int) ($b->capacity ?? 1);
                    $totalCapacity += $qty * $cap;
                    $totalBeds += $qty;
                }

                $loaiPhong->suc_chua = $totalCapacity;
                $loaiPhong->so_giuong = $totalBeds;
                $loaiPhong->save();
            }

            DB::commit();
            return redirect()->route('admin.loai_phong.index')->with('success', 'Thêm loại phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi lưu loại phòng: ' . $e->getMessage()]);
        }
    }


    public function show($id)
    {
        $loaiphong = LoaiPhong::with(['tienNghis', 'bedTypes', 'vatDungs'])->findOrFail($id);
        return view('admin.loai_phong.show', compact('loaiphong'));
    }

    public function edit($id)
    {
        $loaiphong = LoaiPhong::with(['tienNghis', 'vatDungs', 'bedTypes'])->findOrFail($id);
        $tienNghis = TienNghi::where('active', true)->get();
        $vatDungs = VatDung::where('active', true)->where('loai', VatDung::LOAI_DO_DUNG)->get();
        $bedTypes = BedType::orderBy('name')->get();

        return view('admin.loai_phong.edit', compact('loaiphong', 'tienNghis', 'vatDungs', 'bedTypes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ma' => 'required|string|max:50',
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'suc_chua' => 'nullable|integer|min:0',
            'so_giuong' => 'nullable|integer|min:0',
            'gia_mac_dinh' => 'required|numeric',
            'so_luong_thuc_te' => 'required|integer',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'exists:tien_nghi,id',
            'tien_nghi_ids' => 'nullable|array',
            'tien_nghi_ids.*' => 'exists:tien_nghi,id',
            'vat_dungs' => 'nullable|array',
            'vat_dungs.*' => 'exists:vat_dungs,id',
            'vat_dung_ids' => 'nullable|array',
            'vat_dung_ids.*' => 'exists:vat_dungs,id',
            'bed_types' => 'nullable|array',
            'bed_types.*.quantity' => 'nullable|integer|min:0',
            'bed_types.*.price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $loaiPhong = LoaiPhong::findOrFail($id);

            $loaiPhong->update($request->only([
                'ma',
                'ten',
                'mo_ta',
                'gia_mac_dinh',
                'so_luong_thuc_te'
            ]));

            $tienNghiInput = $request->input('tien_nghi', $request->input('tien_nghi_ids', []));
            $loaiPhong->tienNghis()->sync($tienNghiInput);

            $rawVatDungs = $request->input('vat_dungs', $request->input('vat_dung_ids', []));
            $vatIds = [];
            if (!empty($rawVatDungs)) {
                $vatIds = VatDung::whereIn('id', (array)$rawVatDungs)
                    ->where('loai', VatDung::LOAI_DO_DUNG)
                    ->pluck('id')
                    ->toArray();
            }
            $loaiPhong->vatDungs()->sync($vatIds);
            if (!empty($ids)) {
                $phongs = \App\Models\Phong::where('loai_phong_id', $loaiPhong->id)->get();
                foreach ($phongs as $p) {
                    foreach ($vatIds  as $vdId) {
                        $exists = DB::table('phong_vat_dung')
                            ->where('phong_id', $p->id)
                            ->where('vat_dung_id', $vdId)
                            ->exists();
                        if (! $exists) {
                            DB::table('phong_vat_dung')->insert([
                                'phong_id' => $p->id,
                                'vat_dung_id' => $vdId,
                                'so_luong' => 0,
                                'gia_override' => null,
                                'tracked_instances' => (bool) \App\Models\VatDung::find($vdId)->tracked_instances,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }


            if ($request->has('bed_types')) {
                $bedData = $request->input('bed_types', []);

                $hasPositive = false;
                foreach ($bedData as $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty > 0) {
                        $hasPositive = true;
                        break;
                    }
                }
                if (! $hasPositive) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['bed_types' => 'Bạn phải chọn ít nhất 1 loại giường với số lượng > 0.']);
                }

                $sync = [];
                foreach ($bedData as $bedTypeId => $vals) {
                    $qty = isset($vals['quantity']) ? (int)$vals['quantity'] : 0;
                    if ($qty <= 0) continue;
                    $price = isset($vals['price']) && $vals['price'] !== '' ? (float)$vals['price'] : null;
                    $sync[$bedTypeId] = ['quantity' => $qty, 'price' => $price];
                }

                $loaiPhong->bedTypes()->sync($sync);

                $totalCapacity = 0;
                $totalBeds = 0;
                $bedModels = $loaiPhong->bedTypes()->get();
                foreach ($bedModels as $b) {
                    $qty = (int) ($b->pivot->quantity ?? 0);
                    $cap = (int) ($b->capacity ?? 1);
                    $totalCapacity += $qty * $cap;
                    $totalBeds += $qty;
                }

                $loaiPhong->suc_chua = $totalCapacity;
                $loaiPhong->so_giuong = $totalBeds;
                $loaiPhong->save();
            } else {
                $loaiPhong->suc_chua = (int) $request->input('suc_chua', $loaiPhong->suc_chua);
                $loaiPhong->so_giuong = (int) $request->input('so_giuong', $loaiPhong->so_giuong);
                $loaiPhong->save();
            }

            DB::commit();
            return redirect()->route('admin.loai_phong.index')
                ->with('success', 'Cập nhật loại phòng thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Lỗi cập nhật loại phòng: ' . $e->getMessage()]);
        }
    }

    public function disable($id)
    {
        $loaiPhong = LoaiPhong::findOrFail($id);

        $hasOccupied = $loaiPhong->phongs()->where('trang_thai', 'dang_o')->exists();
        if ($hasOccupied) {
            return back()->withErrors(['error' => 'Không thể vô hiệu hoá: tồn tại phòng đang ở thuộc loại này.']);
        }

        DB::transaction(function () use ($loaiPhong) {
            \App\Models\Phong::where('loai_phong_id', $loaiPhong->id)
                ->update(['trang_thai' => 'bao_tri']);

            $loaiPhong->update(['active' => false]);
        });

        return redirect()->route('admin.loai_phong.index')->with('success', 'Đã vô hiệu hoá loại phòng và đặt tất cả phòng thuộc loại này sang trạng thái Bảo trì.');
    }

    public function enable($id)
    {
        $loaiPhong = LoaiPhong::findOrFail($id);
        $loaiPhong->update(['active' => true]);

        return redirect()->route('admin.loai_phong.index')->with('success', 'Đã kích hoạt lại loại phòng.');
    }

    public function getTienNghi($id)
    {
        $loaiPhong = LoaiPhong::with('tienNghis')->findOrFail($id);
        return response()->json($loaiPhong->tienNghis);
    }
}
