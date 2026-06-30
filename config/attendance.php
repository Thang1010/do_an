<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dung sai thời gian chấm công (phút)
    |--------------------------------------------------------------------------
    | - checkin_early_minutes: cho phép chấm công vào sớm tối đa bao nhiêu phút
    |   trước giờ bắt đầu ca.
    | - checkin_after_end: có cho check-in sau khi ca đã kết thúc không.
    | - min_work_minutes: khoảng tối thiểu giữa check-in và check-out, tránh
    |   quét nhầm 2 lần liên tiếp khiến số giờ = 0.
    */
    'checkin_early_minutes' => (int) env('ATTENDANCE_CHECKIN_EARLY_MINUTES', 30),
    'checkin_after_end' => (bool) env('ATTENDANCE_CHECKIN_AFTER_END', false),
    'min_work_minutes' => (int) env('ATTENDANCE_MIN_WORK_MINUTES', 1),

    /*
    |--------------------------------------------------------------------------
    | Hiệu lực link chấm công sau khi quét QR (phút)
    |--------------------------------------------------------------------------
    | QR mở trang xác nhận; trang này sinh một link POST ký ngắn hạn để chống
    | việc gửi lại request sau khi rời quán.
    */
    'submit_ttl_minutes' => (int) env('ATTENDANCE_SUBMIT_TTL_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Geofencing (chống chấm công từ xa bằng GPS)
    |--------------------------------------------------------------------------
    | Khi enabled = true: toạ độ quán suy ra từ địa chỉ (cua_hang.dia_chi) qua
    | OpenStreetMap Nominatim rồi cache lại — không lưu cột toạ độ riêng.
    | Nếu địa chỉ KHÔNG geocode được, việc chấm công sẽ bị CHẶN (fail closed) và
    | yêu cầu quản lý cập nhật địa chỉ đầy đủ, thay vì cho qua như trước.
    | radius_meters: bán kính mặc định cho phép quanh quán.
    */
    'geo' => [
        'enabled' => (bool) env('ATTENDANCE_GEO_ENABLED', false),
        'radius_meters' => (int) env('ATTENDANCE_GEO_RADIUS', 200),
    ],

];
