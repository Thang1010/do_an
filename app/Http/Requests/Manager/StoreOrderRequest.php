<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nguoi_dung_id'           => 'nullable|exists:nguoi_dung,id',
            'ban_an_id'               => 'required_unless:loai_don,đặt hàng trước|nullable|exists:ban_an,id',
            'loai_don'                => 'required|in:đặt hàng trước,sử dụng ngay',
            'phuong_thuc_thanh_toan'  => 'nullable|in:tiền mặt,chuyển khoản',
            'dia_chi_giao_hang'       => 'nullable|string',
            'ghi_chu'                 => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.san_pham_id'     => 'required|integer|exists:san_pham,id',
            'items.*.kich_co_id'      => 'nullable|integer|exists:kich_co,id',
            'items.*.so_luong'        => 'required|integer|min:1|max:1000',
            'items.*.ghi_chu_mon'     => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'loai_don.required'              => 'Vui lòng chọn loại đơn.',
            'loai_don.in'                    => 'Loại đơn hàng không hợp lệ.',
            'ban_an_id.required_unless'      => 'Vui lòng chọn bàn ăn (trừ khi đặt online).',
            'ban_an_id.exists'               => 'Bàn ăn không tồn tại.',
            'items.required'                 => 'Vui lòng thêm ít nhất 1 món vào đơn.',
            'items.*.san_pham_id.required'   => 'Vui lòng chọn sản phẩm cho từng dòng món.',
            'items.*.so_luong.min'           => 'Số lượng mỗi món phải từ 1 trở lên.',
        ];
    }
}
