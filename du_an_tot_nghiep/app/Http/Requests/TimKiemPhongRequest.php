<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimKiemPhongRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cho phép cả guest và user
        return true;
    }

    public function rules(): array
    {
        return [
            'tu_ngay'        => ['required', 'date', 'before:den_ngay'],
            'den_ngay'       => ['required', 'date', 'after:tu_ngay'],
            'so_khach'       => ['nullable', 'integer', 'min:1'],
            'loai_phong_text' => ['nullable', 'string', 'max:255'],
            'loai_phong_id'  => ['nullable', 'integer', 'exists:loai_phong,id'],
            'gia_tu'         => ['nullable', 'integer', 'min:0'],
            'gia_den'        => ['nullable', 'integer', 'min:0'],
            'sap_xep'        => ['nullable', 'in:gia_tang,gia_giam,moi_nhat,cu_nhat'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tu_ngay.required'  => 'Vui lòng chọn ngày nhận phòng.',
            'den_ngay.required' => 'Vui lòng chọn ngày trả phòng.',
            'tu_ngay.before'    => 'Ngày nhận phải trước ngày trả.',
            'den_ngay.after'    => 'Ngày trả phải sau ngày nhận.',
            'loai_phong_id.exists' => 'Loại phòng không hợp lệ.',
        ];
    }
}
