<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phuong_thuc_thanh_toan' => 'required|string',
            'trang_thai_thanh_toan'  => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'trang_thai_thanh_toan.required'  => 'Vui lòng chọn trạng thái thanh toán.',
        ];
    }
}
