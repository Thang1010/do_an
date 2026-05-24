<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_ca'       => ['required', 'string', 'max:100'],
            'ngay_lam'     => ['required', 'date'],
            'gio_bat_dau'  => ['required', 'date_format:H:i'],
            'gio_ket_thuc' => ['required', 'date_format:H:i', 'different:gio_bat_dau'],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_ca.required'           => 'Vui lòng nhập tên ca.',
            'ngay_lam.required'         => 'Vui lòng chọn ngày làm.',
            'gio_bat_dau.required'      => 'Vui lòng nhập giờ bắt đầu.',
            'gio_bat_dau.date_format'   => 'Giờ bắt đầu phải theo định dạng HH:MM.',
            'gio_ket_thuc.required'     => 'Vui lòng nhập giờ kết thúc.',
            'gio_ket_thuc.date_format'  => 'Giờ kết thúc phải theo định dạng HH:MM.',
            'gio_ket_thuc.different'    => 'Giờ kết thúc phải khác giờ bắt đầu.',
        ];
    }
}
