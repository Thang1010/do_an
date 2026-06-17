<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Đồng bộ enum trang_thai của ban_an với code:
     * đổi giá trị cũ 'đặt hàng' thành 'đã đặt' (TableStatus::DA_DAT).
     */
    public function up(): void
    {
        // 1) Mở rộng enum tạm thời để chứa cả giá trị cũ lẫn mới
        DB::statement("ALTER TABLE ban_an MODIFY COLUMN trang_thai ENUM('trống','đang phục vụ','đặt hàng','đã đặt','ngưng sử dụng') NOT NULL DEFAULT 'trống'");
        // 2) Chuyển dữ liệu cũ sang giá trị mới
        DB::statement("UPDATE ban_an SET trang_thai = 'đã đặt' WHERE trang_thai = 'đặt hàng'");
        // 3) Chốt enum cuối cùng (loại bỏ 'đặt hàng')
        DB::statement("ALTER TABLE ban_an MODIFY COLUMN trang_thai ENUM('trống','đang phục vụ','đã đặt','ngưng sử dụng') NOT NULL DEFAULT 'trống'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ban_an MODIFY COLUMN trang_thai ENUM('trống','đang phục vụ','đặt hàng','đã đặt','ngưng sử dụng') NOT NULL DEFAULT 'trống'");
        DB::statement("UPDATE ban_an SET trang_thai = 'đặt hàng' WHERE trang_thai = 'đã đặt'");
        DB::statement("ALTER TABLE ban_an MODIFY COLUMN trang_thai ENUM('trống','đang phục vụ','đặt hàng','ngưng sử dụng') NOT NULL DEFAULT 'trống'");
    }
};
