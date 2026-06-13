<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vai_tro'             => 'required|string|max:50',
            'trang_thai'          => 'required|string|in:hoạt động,bị khóa,ngưng hoạt động',
            'from'                => 'nullable|string|in:customers,staff,staffs,admins',
            'chuc_vu_id'          => 'nullable|integer|exists:chuc_vu,id',
            'ho_ten'              => 'nullable|string|max:70',
            'ngay_sinh'           => 'nullable|date',
            'dia_chi_tam_chu'     => 'nullable|string|max:150',
            'so_dien_thoai'       => 'nullable|string|max:20',
            'loai_hinh_lam_viec'  => 'nullable|string|in:toàn thời gian,bán thời gian',
            'luong_co_ban'        => 'nullable|numeric|min:0',
            'ngay_vao_lam'        => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'vai_tro.required'   => 'Vui lòng chọn vai trò.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái.',
            'trang_thai.in'      => 'Trạng thái không hợp lệ.',
        ];
    }
}
