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
            'email'               => 'required|email|max:60|unique:nguoi_dung,email',
            'password'            => 'required|string|min:8|max:20|confirmed',
            'vai_tro'             => 'required|string|max:50',
            'from'                => 'nullable|string|in:customers,staff,staffs,admins',
            'chuc_vu_id'          => 'nullable|integer|exists:chuc_vu,id',
            'ho_ten'              => 'nullable|string|max:70',
            'ngay_sinh'           => 'nullable|date',
            'dia_chi_tam_chu'     => 'nullable|string|max:150',
            'so_dien_thoai'       => 'nullable|string|max:20',
            'ngay_vao_lam'        => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'               => 'Vui lòng nhập email.',
            'email.email'                  => 'Email không đúng định dạng.',
            'email.unique'                 => 'Email này đã được sử dụng.',
            'email.max'                    => 'Email tối đa 60 ký tự.',
            'password.min'                 => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.max'                 => 'Mật khẩu tối đa 20 ký tự.',
            'password.confirmed'           => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
