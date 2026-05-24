<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ban_an_id'           => 'nullable|exists:ban_an,id',
            'items'               => 'required|array|min:1',
            'items.*.san_pham_id' => 'required|integer|exists:san_pham,id',
            'items.*.kich_co_id'  => 'nullable|integer|exists:kich_co,id',
            'items.*.so_luong'    => 'required|integer|min:1|max:1000',
            'items.*.ghi_chu_mon' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'ban_an_id.exists'             => 'Bàn ăn không tồn tại.',
            'items.required'               => 'Vui lòng thêm ít nhất 1 món vào đơn.',
            'items.*.san_pham_id.required' => 'Vui lòng chọn sản phẩm cho từng dòng món.',
            'items.*.so_luong.min'         => 'Số lượng mỗi món phải từ 1 trở lên.',
        ];
    }
}
