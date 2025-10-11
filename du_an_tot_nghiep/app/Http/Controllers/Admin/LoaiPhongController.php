<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use App\Models\VatDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoaiPhongController extends Controller
{
public function index()
{
    $loaiPhongs = LoaiPhong::with('tienNghis')
        ->withCount([
            'phongs',
            'phongs as occupied_count' => function ($q) {
                $q->where('trang_thai', 'dang_o');
            },
        ])
        ->orderByDesc('id')
        ->get();

    return view('admin.loai_phong.index', compact('loaiPhongs'));
}


    public function create()
    {
        $tienNghis = TienNghi::where('active', true)->get();
        $vatDungs = VatDung::where('active', true)->get();
        return view('admin.loai_phong.create', compact('tienNghis','vatDungs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ma' => 'required|string|max:50|unique:loai_phong,ma',
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'suc_chua' => 'required|integer|min:1',
            'so_giuong' => 'required|integer|min:1',
            'gia_mac_dinh' => 'required|numeric|min:0',
            'so_luong_thuc_te' => 'nullable|integer|min:0',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'exists:tien_nghi,id',
             'vat_dung' => 'nullable|array',
    'vat_dung.*' => 'exists:vat_dungs,id',
        ]);

        $data = $request->only([
            'ma',
            'ten',
            'mo_ta',
            'suc_chua',
            'so_giuong',
            'gia_mac_dinh',
        ]);

        $data['so_luong_thuc_te'] = $request->input('so_luong_thuc_te', 0);

        $loaiPhong = LoaiPhong::create($data);

        if ($request->has('tien_nghi')) {
            $loaiPhong->tienNghis()->sync($request->tien_nghi);
        }
        if ($request->has('vat_dung')) {
    $loaiPhong->vatDungs()->sync($request->vat_dung);
}

        return redirect()->route('admin.loai_phong.index')->with('success', 'Thêm loại phòng thành công');
    }

    public function show($id)
    {
        $loaiphong = LoaiPhong::with(['tienNghis'])->findOrFail($id);
        return view('admin.loai_phong.show', compact('loaiphong'));
    }

    public function edit($id)
    {
        $loaiphong = LoaiPhong::with(['tienNghis', 'vatDungs'])->findOrFail($id);
        $tienNghis = TienNghi::where('active', true)->get();
$vatDungs = VatDung::where('active', true)->get();

        return view('admin.loai_phong.edit', compact('loaiphong', 'tienNghis' ,'vatDungs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ma' => 'required|string|max:50',
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'suc_chua' => 'required|integer',
            'so_giuong' => 'required|integer',
            'gia_mac_dinh' => 'required|numeric',
            'so_luong_thuc_te' => 'required|integer',
        ]);

        $loaiphong = LoaiPhong::findOrFail($id);

        $loaiphong->update($request->only([
            'ma',
            'ten',
            'mo_ta',
            'suc_chua',
            'so_giuong',
            'gia_mac_dinh',
            'so_luong_thuc_te'
        ]));

        $loaiphong->tienNghis()->sync($request->input('tien_nghi_ids', []));
        $loaiphong->vatDungs()->sync($request->input('vat_dung_ids', []));

        return redirect()->route('admin.loai_phong.index')
            ->with('success', 'Cập nhật loại phòng thành công');
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


