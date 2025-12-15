<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage <= 0) $perPage = 15;

        $query = Voucher::query()->withCount('users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'start_date');
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        // Chỉ cho phép một vài cột an toàn
        $allowedSorts = ['start_date', 'end_date', 'qty', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'start_date';
        }
        $query->orderBy($sortBy, $sortDir);

        $vouchers = $query->paginate($perPage)->withQueryString();

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
            'points_required'      => 'nullable|integer|min:0',
        ]);

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['points_required'] = $request->filled('points_required') ? (int)$request->input('points_required') : null;

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
            'points_required'      => 'nullable|integer|min:0',
        ]);

        $validated['active'] = $request->has('active') ? 1 : 0;
        $validated['points_required'] = $request->filled('points_required') ? (int)$request->input('points_required') : null;

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
