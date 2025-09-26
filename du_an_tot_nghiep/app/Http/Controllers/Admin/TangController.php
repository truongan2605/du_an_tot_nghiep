<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TangController extends Controller
{
    public function index()
    {
        $tangList = Tang::all();  // Read: Lấy tất cả (sau có thể paginate cho performance)
        return view('admin.tang.index', compact('tangList'));
    }

    public function create()
    {
        return view('admin.tang.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_tang' => 'required|integer|unique:tang,so_tang',  // Unique check
            'ten' => 'required|string|max:255',
            'ghi_chu' => 'nullable|string',
        ]);

        Tang::create($validated);  // Create
        return redirect()->route('admin.tang.index')->with('success', 'Tầng mới đã được tạo thành công!');
    }

    public function show(Tang $tang)
    {
        return view('admin.tang.show', compact('tang'));  // Read chi tiết
    }

    public function edit(Tang $tang)
    {
        return view('admin.tang.edit', compact('tang'));
    }

    public function update(Request $request, Tang $tang)
    {
        $validated = $request->validate([
            'so_tang' => 'required|integer|unique:tang,so_tang,' . $tang->id,  // Unique ignore self
            'ten' => 'required|string|max:255',
            'ghi_chu' => 'nullable|string',
        ]);

        $tang->update($validated);  // Update
        return redirect()->route('admin.tang.index')->with('success', 'Tầng đã được cập nhật thành công!');
    }

    public function destroy(Tang $tang)
    {
        $tang->delete();  // Delete
        return redirect()->route('admin.tang.index')->with('success', 'Tầng đã được xóa thành công!');
    }
}