<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ho_ten'              => 'required|string|max:150',
            'email'               => 'nullable|email|max:150|unique:nguoi_dung,email',
            'so_dien_thoai'       => 'nullable|digits_between:9,11|unique:nguoi_dung,so_dien_thoai',
            'password'            => 'required|string|min:8|confirmed',
            'vai_tro'             => 'required|string|max:50',
            'from'                => 'nullable|string|in:customers,staff,staffs,admins',
            'chuc_vu_id'          => 'nullable|integer|exists:chuc_vu,id',
            'loai_hinh_lam_viec'  => 'nullable|string|in:toàn thời gian,bán thời gian',
            'luong_co_ban'        => 'nullable|numeric|min:0',
            'ngay_vao_lam'        => 'nullable|date',
            'so_tai_khoan'        => 'nullable|string|max:50',
            'ngan_hang'           => 'nullable|string|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required'              => 'Vui lòng nhập họ tên.',
            'email.email'                  => 'Email không đúng định dạng.',
            'email.unique'                 => 'Email này đã được sử dụng.',
            'so_dien_thoai.digits_between' => 'Số điện thoại phải từ 9 đến 11 chữ số.',
            'so_dien_thoai.unique'         => 'Số điện thoại này đã được sử dụng.',
            'password.min'                 => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed'           => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
