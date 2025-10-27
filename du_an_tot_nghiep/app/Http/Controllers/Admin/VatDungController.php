<?php

namespace App\Http\Controllers\Admin;

use App\Models\VatDung;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Phong;
use App\Models\LoaiPhong;

class VatDungController extends Controller
{

    public function index(Request $request)
    {
        $query = VatDung::query();

        if ($request->filled('keyword')) {
            $query->where('ten', 'like', '%' . $request->keyword . '%');
        }

        $vatdungs = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.vat-dung.index', compact('vatdungs'));
    }

    public function create()
    {
        return view('admin.vat-dung.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia' => 'nullable|numeric|min:0',
            'loai' => 'required|in:do_an,do_dung',
            'tracked_instances' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'active' => 'nullable|boolean',
        ]);

        $data = $request->only(['ten', 'mo_ta', 'gia', 'loai']);
        $data['gia'] = $request->filled('gia') ? (float)$request->gia : ($data['gia'] ?? 0);
        $data['tracked_instances'] = $request->boolean('tracked_instances', false);
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('vatdung_icons', 'public');
            $data['icon'] = $iconPath;
        }

        VatDung::create($data);

        return redirect()->route('admin.vat-dung.index')
            ->with('success', 'Vật dụng đã được tạo thành công!');
    }


    public function show(VatDung $vat_dung)
    {
        $loaiPhongs = $vat_dung->loaiPhongs()->orderBy('id', 'desc')->paginate(10);

        return view('admin.vat-dung.show', compact('vat_dung', 'loaiPhongs'));
    }


    public function edit(VatDung $vatDung)
    {
        return view('admin.vat-dung.edit', compact('vatDung'));
    }


    public function update(Request $request, VatDung $vatDung)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'gia' => 'nullable|numeric|min:0',
            'loai' => 'required|in:do_an,do_dung',
            'tracked_instances' => 'nullable|boolean',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'active' => 'nullable|boolean',
        ]);

        $data = $request->only(['ten', 'mo_ta', 'loai']);
        $data['gia'] = $request->filled('gia') ? (float)$request->gia : ($vatDung->gia ?? 0);
        $data['tracked_instances'] = $request->boolean('tracked_instances', $vatDung->tracked_instances ?? false);
        $data['active'] = $request->boolean('active', $vatDung->active ?? true);

        if ($request->hasFile('icon')) {
            if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                try {
                    Storage::disk('public')->delete($vatDung->icon);
                } catch (\Throwable $e) {
                    // không block nếu xóa thất bại, chỉ log nếu cần
                }
            }
            $data['icon'] = $request->file('icon')->store('vatdung_icons', 'public');
        }

        $vatDung->update($data);

        return redirect()
            ->route('admin.vat-dung.index')
            ->with('success', 'Vật dụng đã được cập nhật thành công!');
    }



    private function hasRelatedOccupiedRooms(VatDung $vatDung): bool
    {
        $loaiPhongIds = $vatDung->loaiPhongs()->pluck('id')->toArray();

        $query = Phong::query()->where('trang_thai', 'dang_o');

        $query->where(function ($q) use ($vatDung, $loaiPhongIds) {
            $q->whereHas('vatDungs', function ($qq) use ($vatDung) {
                $qq->where('vat_dungs.id', $vatDung->id);
            });

            if (!empty($loaiPhongIds)) {
                $q->orWhereIn('loai_phong_id', $loaiPhongIds);
            }
        });

        return $query->exists();
    }

    public function toggleActive(VatDung $vatDung)
    {
        if ($vatDung->active) {
            if ($this->hasRelatedOccupiedRooms($vatDung)) {
                return redirect()->back()->withErrors([
                    'error' => 'Không thể vô hiệu hóa vật dụng này vì có ít nhất một phòng đang ở (dang_o) liên quan đến vật dụng này.'
                ]);
            }
            $vatDung->active = false;
            $vatDung->save();

            return redirect()->back()->with('success', 'Vật dụng đã được vô hiệu hóa thành công.');
        }

        $vatDung->active = true;
        $vatDung->save();

        return redirect()->back()->with('success', 'Vật dụng đã được kích hoạt thành công.');
    }

    public function destroy(VatDung $vatDung)
    {
        if ($this->hasRelatedOccupiedRooms($vatDung)) {
            return redirect()->back()->withErrors([
                'error' => 'Không thể xóa vật dụng này vì có ít nhất một phòng đang ở (dang_o) liên quan đến vật dụng.'
            ]);
        }

        DB::beginTransaction();
        try {

            if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                Storage::disk('public')->delete($vatDung->icon);
            }

            if (method_exists($vatDung, 'loaiPhongs')) {
                $vatDung->loaiPhongs()->detach();
            }
            if (method_exists($vatDung, 'phongs')) {
                $vatDung->phongs()->detach();
            }

            $vatDung->delete();
            DB::commit();

            return redirect()->route('admin.vat-dung.index')
                ->with('success', 'Vật dụng đã được xóa thành công!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi xóa vật dụng: ' . $e->getMessage()]);
        }
    }
}
