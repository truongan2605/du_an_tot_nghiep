<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use Illuminate\Http\Request;

class LoaiPhongController extends Controller
{
    public function index()
    {
        $loaiPhongs = LoaiPhong::with('tienNghis')->get();
        return view('admin.loai_phong.index', compact('loaiPhongs'));
    }

    public function create()
    {
        $tienNghis = TienNghi::where('active', true)->get();
        return view('admin.loai_phong.create', compact('tienNghis'));
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
            'so_luong_thuc_te' => 'required|integer|min:0',
            'tien_nghi' => 'nullable|array',
            'tien_nghi.*' => 'exists:tien_nghi,id',
        ]);

        $loaiPhong = LoaiPhong::create($request->only([
            'ma', 'ten', 'mo_ta', 'suc_chua', 'so_giuong', 'gia_mac_dinh', 'so_luong_thuc_te'
        ]));

        if ($request->has('tien_nghi')) {
            $loaiPhong->tienNghis()->sync($request->tien_nghi);
        }

        return redirect()->route('admin.loai_phong.index')->with('success', 'Thêm loại phòng thành công');
    }

 public function show($id)
{
    $loaiphong = loaiphong::with(['tienNghis'])->findOrFail($id);
    return view('admin.loai_phong.show', compact('loaiphong'));
}

public function edit($id)
{
    $loaiphong = LoaiPhong::with('tienNghis')->findOrFail($id);
    $tienNghis = TienNghi::where('active', true)->get();

    return view('admin.loai_phong.edit', compact('loaiphong', 'tienNghis'));
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

    // Cập nhật dữ liệu chính
    $loaiphong->update($request->only([
        'ma', 'ten', 'mo_ta', 'suc_chua', 'so_giuong', 'gia_mac_dinh', 'so_luong_thuc_te'
    ]));

    // Cập nhật tiện nghi (pivot table)
    $loaiphong->tienNghis()->sync($request->input('tien_nghi_ids', []));

    return redirect()->route('admin.loai_phong.index')
        ->with('success', 'Cập nhật loại phòng thành công');
}



    public function destroy(LoaiPhong $loaiphong)
    {
        $loaiphong->tienNghis()->detach();
        $loaiphong->delete();
        return redirect()->route('admin.loai_phong.index')->with('success', 'Xóa loại phòng thành công');
    }

public function getTienNghi($id)
{
    $loaiPhong = LoaiPhong::with('tienNghis')->findOrFail($id);
    return response()->json($loaiPhong->tienNghis);
}
}