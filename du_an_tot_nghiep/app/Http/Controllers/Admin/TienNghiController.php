<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TienNghi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class TienNghiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tienNghis = TienNghi::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.tien-nghi.index', compact('tienNghis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tien-nghi.create');
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

    TienNghi::create($data);

    return redirect()->route('admin.tien-nghi.index')
        ->with('success', 'Tiện nghi đã được tạo thành công!');
}

    /**
     * Display the specified resource.
     */
    public function show(TienNghi $tienNghi)
    {
        return view('admin.tien-nghi.show', compact('tienNghi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TienNghi $tienNghi)
    {
        return view('admin.tien-nghi.edit', compact('tienNghi'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TienNghi $tienNghi)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia' => 'required|numeric|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'boolean'
        ]);

        $data = $request->all();
        
        // Handle icon upload
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($tienNghi->icon && Storage::disk('public')->exists($tienNghi->icon)) {
                Storage::disk('public')->delete($tienNghi->icon);
            }
            
            $iconPath = $request->file('icon')->store('icons', 'public');
            $data['icon'] = $iconPath;
        }

        $data['active'] = $request->has('active');

        $tienNghi->update($data);

        return redirect()->route('admin.tien-nghi.index')
            ->with('success', 'Tiện nghi đã được cập nhật thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TienNghi $tienNghi)
    {
        // Delete icon if exists
        if ($tienNghi->icon && Storage::disk('public')->exists($tienNghi->icon)) {
            Storage::disk('public')->delete($tienNghi->icon);
        }

        $tienNghi->delete();

        return redirect()->route('admin.tien-nghi.index')
            ->with('success', 'Tiện nghi đã được xóa thành công!');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(TienNghi $tienNghi)
    {
        $tienNghi->update(['active' => !$tienNghi->active]);
        
        $status = $tienNghi->active ? 'kích hoạt' : 'vô hiệu hóa';
        return redirect()->back()
            ->with('success', "Tiện nghi đã được {$status} thành công!");
    }
}
