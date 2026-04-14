<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('don_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ma_don_hang', 50)->unique();
            $table->foreignId('nguoi_dung_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->foreignId('nhan_vien_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->foreignId('ban_an_id')->nullable()->constrained('ban_an')->nullOnDelete();
            $table->foreignId('voucher_nguoi_dung_id')->nullable()->constrained('voucher_nguoi_dung')->nullOnDelete();

            $table->enum('loai_don', ['đặt online', 'mua tại quán', 'gọi tại bàn bằng qr']);
            $table->enum('trang_thai_don', [
                'chờ xác nhận',
                'đã xác nhận',
                'đã hủy'
            ])->default('chờ xác nhận');

            $table->enum('trang_thai_thanh_toan', [
                'chưa thanh toán',
                'đã thanh toán'
            ])->default('chưa thanh toán');

            $table->enum('phuong_thuc_thanh_toan', [
                'tiền mặt',
                'chuyển khoản'
            ])->nullable();

            $table->decimal('tam_tinh', 12, 2)->default(0);
            $table->decimal('so_tien_giam', 12, 2)->default(0);
            $table->decimal('tong_tien', 12, 2)->default(0);
            $table->text('ghi_chu')->nullable();
            $table->string('ten_khach_hang', 150)->nullable();
            $table->string('so_dien_thoai_khach', 20)->nullable();
            $table->text('dia_chi_giao_hang')->nullable();
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'trang_thai_don']);
            $table->index(['created_at', 'trang_thai_don']);
            $table->index('ma_don_hang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_hang');
    }
};
