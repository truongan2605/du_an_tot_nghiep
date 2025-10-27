<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VatDungIncident;
use App\Models\PhongVatDungInstance;
use Illuminate\Support\Facades\Auth;

class VatDungIncidentController extends Controller
{
    public function createInstance(Request $request, $phongId)
    {
        $data = $request->validate([
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'serial' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        $data['phong_id'] = $phongId;
        $data['created_by'] = Auth::id();

        $instance = PhongVatDungInstance::create($data);

        return back()->with('success', 'Tạo bản vật dụng thành công');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phong_vat_dung_instance_id' => 'nullable|exists:phong_vat_dung_instances,id',
            'phong_id' => 'nullable|exists:phong,id',
            'dat_phong_id' => 'nullable|exists:dat_phong,id',
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
        ]);

        $data['reported_by'] = Auth::id();

        $incident = VatDungIncident::create($data);

        return back()->with('success', 'Ghi nhận sự cố thành công');
    }

    public function update(Request $request, VatDungIncident $incident)
    {
        $data = $request->validate([
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'fee' => 'nullable|numeric|min:0',
        ]);

        $incident->update($data);
        return back()->with('success', 'Cập nhật sự cố thành công');
    }

    public function destroy(VatDungIncident $incident)
    {
        if ($incident->billed_at) {
            return back()->withErrors(['error' => 'Sự cố đã tính hoá đơn, không thể xóa.']);
        }
        $incident->delete();
        return back()->with('success', 'Xóa sự cố thành công');
    }
}
