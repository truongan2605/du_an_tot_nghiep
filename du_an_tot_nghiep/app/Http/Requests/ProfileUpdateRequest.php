<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : null;

        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($userId)],
            'avatar' => ['nullable','image','max:2048'],
            'so_dien_thoai' => ['nullable','string','max:50'],
            'country' => ['nullable','string','max:100'],
            'dob' => ['nullable','date'],
            'gender' => ['nullable','in:male,female,other'],
            'address' => ['nullable','string','max:1000'],
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email này đã được sử dụng.',
            'avatar.image' => 'Avatar phải là file ảnh.',
            'avatar.max' => 'Avatar tối đa 2MB.',
        ];
    }
}
