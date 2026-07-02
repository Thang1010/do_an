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
            // Bàn bắt buộc cho đơn tại quán (sử dụng ngay) và đặt trước; đơn mang về không cần bàn.
            'ban_an_id'               => 'required_if:loai_don,sử dụng ngay,đặt hàng trước|nullable|exists:ban_an,id',
            'loai_don'                => 'required|in:sử dụng ngay,đặt hàng trước,mang về',
            // Đặt trước = ngồi tại quán theo giờ hẹn → bắt buộc hẹn giờ đến (giống khách thành viên).
            'thoi_gian_den'           => 'nullable|required_if:loai_don,đặt hàng trước|date_format:H:i',
            'phuong_thuc_thanh_toan'  => 'nullable|in:tiền mặt,chuyển khoản',
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
            'ban_an_id.required_if'          => 'Vui lòng chọn bàn ăn cho đơn tại quán / đặt trước.',
            'ban_an_id.exists'               => 'Bàn ăn không tồn tại.',
            'thoi_gian_den.required_if'      => 'Vui lòng chọn giờ hẹn đến cho đơn đặt trước.',
            'thoi_gian_den.date_format'      => 'Giờ hẹn đến không hợp lệ.',
            'items.required'                 => 'Vui lòng thêm ít nhất 1 món vào đơn.',
            'items.*.san_pham_id.required'   => 'Vui lòng chọn sản phẩm cho từng dòng món.',
            'items.*.so_luong.min'           => 'Số lượng mỗi món phải từ 1 trở lên.',
        ];
    }
}
