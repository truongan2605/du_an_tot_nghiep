<?php

namespace App\Http\Controllers\Admin;

use App\Models\TienNghi;
use App\Models\VatDung;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class TienNghiController extends Controller
{

    public function index()
    {
        $tienNghis = TienNghi::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.tien-nghi.index', compact('tienNghis'));
    }


    public function create()
    {
        return view('admin.tien-nghi.create');
    }


    public function store(Request $request)
    {
        $request->merge([
            'gia' => $request->gia ? str_replace('.', '', $request->gia) : null
        ]);

        $request->validate([
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia' => 'required|numeric|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'active' => 'boolean'
        ]);

        $data = $request->only(['ten', 'mo_ta', 'gia', 'active']);

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('icons', 'public');
            $data['icon'] = $iconPath;
        }

        $data['active'] = $request->has('active');

        DB::transaction(function () use ($data, $request) {
            // Tạo TienNghi
            $tienNghi = TienNghi::create($data);

            // Tự động tạo VatDung với loại 'dich_vu_khac'
            $vatDungData = [
                'ten' => $data['ten'],
                'mo_ta' => $data['mo_ta'] ?? null,
                'gia' => $data['gia'] ?? 0,
                'loai' => VatDung::LOAI_DICH_VU_KHAC,
                'active' => $data['active'],
                'tracked_instances' => false, // Dịch vụ không cần tracked instances
            ];

            // Copy icon nếu có
            if (isset($data['icon'])) {
                $vatDungData['icon'] = $data['icon'];
            }

            VatDung::create($vatDungData);
        });

        return redirect()->route('admin.tien-nghi.index')
            ->with('success', 'Dịch vụ đã được tạo thành công và đã được thêm vào quản lý vật dụng phòng!');
    }

    public function show(TienNghi $tienNghi)
    {
        $rooms = $tienNghi->phongs()
            ->with(['loaiPhong', 'tang'])
            ->orderBy('ma_phong')
            ->paginate(12);

        return view('admin.tien-nghi.show', compact('tienNghi', 'rooms'));
    }


    public function edit(TienNghi $tienNghi)
    {
        return view('admin.tien-nghi.edit', compact('tienNghi'));
    }

    public function update(Request $request, TienNghi $tienNghi)
    {
        $request->merge([
            'gia' => $request->gia ? str_replace('.', '', $request->gia) : null
        ]);

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

        DB::transaction(function () use ($tienNghi, $data) {
            // Lưu tên cũ trước khi update
            $oldTen = $tienNghi->ten;
            
            // Cập nhật TienNghi
            $tienNghi->update($data);

            // Tìm VatDung tương ứng bằng tên cũ hoặc tên mới
            $vatDung = VatDung::where(function($query) use ($oldTen, $data) {
                $query->where('ten', $oldTen)
                      ->orWhere('ten', $data['ten']);
            })
            ->where('loai', VatDung::LOAI_DICH_VU_KHAC)
            ->first();

            if ($vatDung) {
                $vatDungData = [
                    'ten' => $data['ten'],
                    'mo_ta' => $data['mo_ta'] ?? null,
                    'gia' => $data['gia'] ?? 0,
                    'active' => $data['active'],
                ];

                // Cập nhật icon nếu có
                if (isset($data['icon'])) {
                    // Xóa icon cũ nếu có
                    if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                        Storage::disk('public')->delete($vatDung->icon);
                    }
                    $vatDungData['icon'] = $data['icon'];
                }

                $vatDung->update($vatDungData);
            } else {
                // Nếu không tìm thấy, tạo mới VatDung
                $vatDungData = [
                    'ten' => $data['ten'],
                    'mo_ta' => $data['mo_ta'] ?? null,
                    'gia' => $data['gia'] ?? 0,
                    'loai' => VatDung::LOAI_DICH_VU_KHAC,
                    'active' => $data['active'],
                    'tracked_instances' => false,
                ];

                if (isset($data['icon'])) {
                    $vatDungData['icon'] = $data['icon'];
                }

                VatDung::create($vatDungData);
            }
        });

        return redirect()->route('admin.tien-nghi.index')
            ->with('success', 'Dịch vụ đã được cập nhật thành công!');
    }


    public function destroy(TienNghi $tienNghi)
    {
        DB::transaction(function () use ($tienNghi) {
            // Xóa icon của TienNghi nếu có
            if ($tienNghi->icon && Storage::disk('public')->exists($tienNghi->icon)) {
                Storage::disk('public')->delete($tienNghi->icon);
            }

            // Tìm và xóa VatDung tương ứng (nếu có)
            $vatDung = VatDung::where('ten', $tienNghi->ten)
                ->where('loai', VatDung::LOAI_DICH_VU_KHAC)
                ->first();

            if ($vatDung) {
                // Xóa icon của VatDung nếu có
                if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                    Storage::disk('public')->delete($vatDung->icon);
                }
                $vatDung->delete();
            }

            // Xóa TienNghi
            $tienNghi->delete();
        });

        return redirect()->route('admin.tien-nghi.index')
            ->with('success', 'Dịch vụ đã được xóa thành công!');
    }

    public function toggleActive(TienNghi $tienNghi)
    {
        $tienNghi->update(['active' => !$tienNghi->active]);

        $status = $tienNghi->active ? 'kích hoạt' : 'vô hiệu hóa';
        return redirect()->back()
            ->with('success', "Dịch vụ đã được {$status} thành công!");
    }
}
