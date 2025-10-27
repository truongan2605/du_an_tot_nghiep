<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhongVatDungConsumption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PhongConsumptionController extends Controller
{
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'phong_id' => 'nullable|exists:phong,id',
            'vat_dung_id' => 'required|exists:vat_dungs,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        $datPhong = \App\Models\DatPhong::find($data['dat_phong_id']);
        if (!$datPhong || $datPhong->trang_thai !== 'dang_su_dung') {
            return back()->withErrors(['error' => 'Chỉ được thêm tiêu thụ khi booking đang ở trạng thái "dang_su_dung".']);
        }

        $data['created_by'] = Auth::id(); 
        $data['consumed_at'] = now();

        $cons = PhongVatDungConsumption::create($data);

        return back()->with('success', 'Thêm món tiêu thụ thành công');
    }

    public function update(Request $request, PhongVatDungConsumption $consumption)
    {
        $this->authorize('update', $consumption); // optional, nếu bạn có chính sách

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($consumption->billed_at) {
            return back()->withErrors(['error' => 'Món này đã được tính hoá đơn, không thể chỉnh.']);
        }

        $consumption->update($data);
        return back()->with('success', 'Cập nhật thành công');
    }

    public function destroy(PhongVatDungConsumption $consumption)
    {
        if ($consumption->billed_at) {
            return back()->withErrors(['error' => 'Món này đã được tính hoá đơn, không thể xóa.']);
        }
        $consumption->delete();
        return back()->with('success', 'Xóa thành công');
    }
}
