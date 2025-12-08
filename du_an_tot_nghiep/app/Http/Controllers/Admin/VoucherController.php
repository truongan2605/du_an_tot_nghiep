<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        // Lấy thêm số user đã nhận để tính "số lượng còn lại" ở view
        $vouchers = Voucher::withCount('users')->get();

        return view('admin.voucher.index', compact('vouchers'));
    }

    public function create()
    {
        // Form dùng chung: sẽ không có biến $voucher
        return view('admin.voucher.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|unique:voucher,name',
            'code'                 => 'required|unique:voucher,code',
            'type'                 => 'required|in:fixed,percent',
            'value'                => 'required|numeric|min:0',
            'qty'                  => 'required|integer|min:1',
            'usage_limit_per_user' => 'required|integer|min:1',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'active'               => 'nullable|boolean',
        ]);

        // Checkbox: nếu không tick thì không gửi; ép về 0/1
        $validated['active'] = $request->has('active') ? 1 : 0;

        Voucher::create($validated);

        return redirect()
            ->route('admin.voucher.index')
            ->with('success', 'Thêm voucher thành công!');
    }

    public function show(Voucher $voucher)
    {
        return view('admin.voucher.show', compact('voucher'));
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.voucher.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'name'                 => 'required|unique:voucher,name,' . $voucher->id,
            'code'                 => 'required|unique:voucher,code,' . $voucher->id,
            'type'                 => 'required|in:fixed,percent',
            'value'                => 'required|numeric|min:0',
            'qty'                  => 'required|integer|min:1',
            'usage_limit_per_user' => 'required|integer|min:1',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'active'               => 'nullable|boolean',
        ]);

        $validated['active'] = $request->has('active') ? 1 : 0;

        $voucher->update($validated);

        return redirect()
            ->route('admin.voucher.index')
            ->with('success', 'Cập nhật voucher thành công!');
    }

    /**
     * Vô hiệu hóa voucher (không xóa khỏi CSDL).
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->active = 0;
        $voucher->save();

        return redirect()
            ->route('admin.voucher.index')
            ->with('success', 'Đã vô hiệu hóa voucher thành công!');
    }
}
