<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoaiPhongRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loaiPhong = $this->route('loaiphong') ?? $this->route('id');
        $loaiPhongId = $loaiPhong instanceof \App\Models\LoaiPhong ? $loaiPhong->id : $loaiPhong;

        return [
            'ma'                   => 'required|string|max:50|unique:loai_phong,ma,' . $loaiPhongId,
            'ten'                  => 'required|string|max:255',
            'mo_ta'                => 'nullable|string',
            'gia_mac_dinh'         => 'required',
            'so_luong_thuc_te'     => 'required|integer',

            // Tien nghi:
            'tien_nghi_ids'        => 'sometimes|array',
            'tien_nghi_ids.*'      => 'exists:tien_nghi,id',
            'tien_nghi_prices'     => 'sometimes|array',
            'tien_nghi_prices.*'   => 'nullable|string', // để trống hoặc chuỗi số có dấu chấm

            // Vat dung:
            'vat_dung_ids'         => 'sometimes|array',
            'vat_dung_ids.*'       => 'exists:vat_dungs,id',

            // Bed types:
            'bed_types'            => 'sometimes|array',
            'bed_types.*.quantity' => 'nullable|integer|min:0',
            'bed_types.*.price'    => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'ma.unique' => 'Mã loại phòng đã tồn tại.',
            'tien_nghi_prices.*.regex' => 'Giá tiện nghi không hợp lệ.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // Clean giá mặc định
        if (isset($data['gia_mac_dinh'])) {
            $data['gia_mac_dinh'] = (int) preg_replace('/\D/', '', $data['gia_mac_dinh']);
        }

        // Clean gia rieng tien nghi
        if (isset($data['tien_nghi_prices'])) {
            foreach ($data['tien_nghi_prices'] as $id => $price) {
                if (blank($price)) {
                    $data['tien_nghi_prices'][$id] = null;
                } else {
                    $clean = (int) preg_replace('/\D/', '', $price);     // ← BỎ HẾT DẤU CHẤM, CHỈ GIỮ SỐ
                    $data['tien_nghi_prices'][$id] = $clean > 0 ? $clean : null;
                }
            }
        }

        return $data;
    }
}
