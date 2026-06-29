<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ngưỡng giờ làm theo loại hình (dùng để CẢNH BÁO khi phân ca)
    |--------------------------------------------------------------------------
    | Đây là cảnh báo mềm: hệ thống vẫn cho phép xếp ca, chỉ hiển thị
    | badge/cảnh báo để quản lý tự cân nhắc, không chặn cứng.
    |
    | - gio_toi_da_tuan_part_time: trần giờ/tuần cho nhân viên BÁN thời gian.
    | - gio_chuan_tuan_full_time : giờ chuẩn/tuần cho nhân viên TOÀN thời gian
    |                              (vượt mức này coi như tăng ca - OT).
    */

    'gio_toi_da_tuan_part_time' => (int) env('SHIFT_PART_TIME_MAX_WEEK', 24),

    'gio_chuan_tuan_full_time' => (int) env('SHIFT_FULL_TIME_STD_WEEK', 48),
];
