<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ca_lam_viec_id'        => 'required|exists:ca_lam_viec,id',
            'nguyen_lieu_id'        => 'required|exists:nguyen_lieu,id',
            'so_luong'              => 'required|numeric|min:0.01',
            'don_gia'               => 'required|numeric|min:0',
            'phuong_thuc_thanh_toan' => 'required|string|max:50',
            'ghi_chu'               => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'ca_lam_viec_id.required' => 'Vui lòng chọn ca làm việc.',
            'ca_lam_viec_id.exists'   => 'Ca làm việc không tồn tại.',
            'nguyen_lieu_id.required' => 'Vui lòng chọn nguyên liệu.',
            'so_luong.required'       => 'Vui lòng nhập số lượng.',
            'so_luong.min'            => 'Số lượng phải lớn hơn 0.',
            'don_gia.required'        => 'Vui lòng nhập đơn giá.',
        ];
    }
}
