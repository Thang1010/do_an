<?php

namespace App\Enums;

/**
 * OrderStatus đã được loại bỏ — trạng thái đơn hàng không còn lưu trong DB.
 * Đơn hàng chỉ phân biệt qua trang_thai_thanh_toan (chưa thanh toán / đã thanh toán).
 * File này được giữ lại để tránh lỗi class-not-found trên các import cũ.
 */
enum OrderStatus: string
{
    // Không còn case nào — trang_thai_don đã bị xóa khỏi bảng don_hang.
}
