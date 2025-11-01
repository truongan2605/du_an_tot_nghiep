<?php

namespace App\Http\Controllers\Admin;

use App\Models\VatDung;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Phong;
use App\Models\LoaiPhong;
use Illuminate\Support\Facades\Auth;

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
        $newTracked = $request->boolean('tracked_instances', false);

        if (($data['loai'] ?? null) === VatDung::LOAI_DO_AN && $newTracked) {
            return back()->withInput()->withErrors(['tracked_instances' => 'Không được bật theo dõi bản (tracked_instances) cho vật dụng loại "Đồ ăn".']);
        }

        $data['gia'] = $request->filled('gia') ? (float)$request->gia : ($data['gia'] ?? 0);
        $data['tracked_instances'] = $newTracked;
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

        // Lấy dữ liệu đầu vào
        $newLoai = $request->input('loai', $vatDung->loai);
        $newTracked = $request->boolean('tracked_instances', $vatDung->tracked_instances ?? false);

        // Không cho bật tracked_instances cho do_an
        if ($newLoai === VatDung::LOAI_DO_AN && $newTracked) {
            return back()->withInput()->withErrors(['tracked_instances' => 'Không được bật theo dõi bản (tracked_instances) cho vật dụng loại "Đồ ăn".']);
        }

        $data = $request->only(['ten', 'mo_ta', 'loai']);
        $data['gia'] = $request->filled('gia') ? (float)$request->gia : ($vatDung->gia ?? 0);
        $data['tracked_instances'] = $newTracked;
        $data['active'] = $request->boolean('active', $vatDung->active ?? true);

        if ($request->hasFile('icon')) {
            if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                try {
                    Storage::disk('public')->delete($vatDung->icon);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $data['icon'] = $request->file('icon')->store('vatdung_icons', 'public');
        }

        DB::transaction(function () use ($vatDung, $data) {
            $oldTracked = (bool)$vatDung->tracked_instances;
            $vatDung->update($data);
            $newTracked = (bool)$data['tracked_instances'];

            if (!$oldTracked && $newTracked) {
                $pivots = DB::table('phong_vat_dung')->where('vat_dung_id', $vatDung->id)->get();
                foreach ($pivots as $pv) {
                    $qty = (int)($pv->so_luong ?? 0);
                    for ($i = 0; $i < $qty; $i++) {
                        DB::table('phong_vat_dung_instances')->insert([
                            'phong_id' => $pv->phong_id,
                            'vat_dung_id' => $vatDung->id,
                            'serial' => null,
                            'status' => 'ok',
                            'created_by' => Auth::id() ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if ($oldTracked && !$newTracked) {
                $instances = DB::table('phong_vat_dung_instances')
                    ->where('vat_dung_id', $vatDung->id)
                    ->where('status', 'ok')
                    ->get()
                    ->groupBy('phong_id');

                foreach ($instances as $phongId => $rows) {
                    $aliveCount = count($rows);
                    DB::table('phong_vat_dung')
                        ->updateOrInsert(
                            ['phong_id' => $phongId, 'vat_dung_id' => $vatDung->id],
                            ['so_luong' => $aliveCount, 'updated_at' => now()]
                        );
                }
                DB::table('phong_vat_dung_instances')->where('vat_dung_id', $vatDung->id)->update(['status' => 'archived', 'updated_at' => now()]);
            }
        });

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
                'error' => 'Không thể xóa vật dụng này vì có ít nhất một phòng đang ở liên quan đến vật dụng.'
            ]);
        }

        DB::beginTransaction();
        try {
            if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon)) {
                Storage::disk('public')->delete($vatDung->icon);
            }

            DB::table('phong_vat_dung_instances')->where('vat_dung_id', $vatDung->id)->delete();
            DB::table('phong_vat_dung_consumptions')->where('vat_dung_id', $vatDung->id)->delete();
            $vatDung->loaiPhongs()->detach();
            $vatDung->phongs()->detach();
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
