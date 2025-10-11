<?php

namespace App\Http\Controllers\Admin;

use App\Models\VatDung;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;


class VatDungController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vatdungs = VatDung::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.vat-dung.index', compact('vatdungs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.vat-dung.create');
    }

    /**
     * Store a newly created resource in storage.
     */
 public function store(Request $request)
{
    $request->validate([
        'ten' => 'required|string|max:255',
        'mo_ta' => 'nullable|string',
        'gia' => 'required|numeric|min:0',
        'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'active' => 'boolean'
    ]);

    $data = $request->only(['ten','mo_ta','gia','active']);

    // Handle icon upload
    if ($request->hasFile('icon')) {
        $iconPath = $request->file('icon')->store('icons', 'public');
        $data['icon'] = $iconPath;
    }

    $data['active'] = $request->has('active');

    VatDung::create($data);

    return redirect()->route('admin.vat-dung.index')
        ->with('success', 'Tiện nghi đã được tạo thành công!');
}

    /**
     * Display the specified resource.
     */
 public function show(VatDung $vat_dung)
    {
        // Lấy danh sách loại phòng có chứa vật dụng này (nếu có quan hệ many-to-many)
        $loaiPhongs = $vat_dung->loaiPhongs()->paginate(10);

        return view('admin.vat-dung.show', compact('vat_dung', 'loaiPhongs'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VatDung $vatDung)
    {
        return view('admin.vat-dung.edit', compact('vatDung'));
    }

    /**
     * Cập nhật vật dụng
     */
    public function update(Request $request, VatDung $vatDung)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia' => 'required|numeric|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'boolean'
        ]);

        $data = $request->only(['ten', 'mo_ta', 'gia', 'active']);

        // Xử lý upload ảnh
        if ($request->hasFile('icon')) {
            // Xóa icon cũ nếu có
            if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                Storage::disk('public')->delete($vatDung->icon);
            }

            $data['icon'] = $request->file('icon')->store('vatdung_icons', 'public');
        }

        // Nếu checkbox không check thì set = false
        $data['active'] = $request->has('active');

        $vatDung->update($data);

        return redirect()
            ->route('admin.vat-dung.index')
            ->with('success', 'Vật dụng đã được cập nhật thành công!');
    }


    /**
     * Remove the specified resource from storage.
     */
   public function destroy(VatDung $vatDung)
{
    // Xóa ảnh nếu có
    if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
        Storage::disk('public')->delete($vatDung->icon);
    }

    // Xóa vật dụng
    $vatDung->delete();

    return redirect()->route('admin.vat-dung.index')
        ->with('success', 'Vật dụng đã được xóa thành công!');
}



    /**
     * Toggle active status
     */
 public function toggleActive(VatDung $vatDung)
{
    $vatDung->active = !$vatDung->active;
    $vatDung->save();

    $status = $vatDung->active ? 'kích hoạt' : 'vô hiệu hóa';

    return redirect()->back()
        ->with('success', "Vật dụng đã được {$status} thành công!");
}

}

