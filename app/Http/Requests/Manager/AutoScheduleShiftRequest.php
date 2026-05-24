<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class AutoScheduleShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from'                   => ['required', 'date'],
            'date_to'                     => ['required', 'date', 'after_or_equal:date_from'],
            'shifts_per_day'              => ['required', 'integer', 'min:1', 'max:3'],
            'auto_manager_count'          => ['nullable', 'integer', 'min:0'],
            'auto_position_counts'        => ['nullable', 'array'],
            'auto_position_counts.*'      => ['nullable', 'integer', 'min:0'],
            'auto_position_labels'        => ['nullable', 'array'],
            'auto_position_labels.*'      => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.required'          => 'Vui lòng chọn ngày bắt đầu.',
            'date_to.required'            => 'Vui lòng chọn ngày kết thúc.',
            'date_to.after_or_equal'      => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'shifts_per_day.required'     => 'Vui lòng nhập số ca mỗi ngày.',
            'shifts_per_day.min'          => 'Số ca mỗi ngày phải ít nhất 1.',
            'shifts_per_day.max'          => 'Số ca mỗi ngày tối đa là 3.',
        ];
    }
}
