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
        Schema::create('chi_tiet_don_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')
                ->constrained('don_hang')
                ->cascadeOnDelete();
            $table->foreignId('san_pham_id')
                ->constrained('san_pham')
                ->restrictOnDelete();
            $table->foreignId('kich_co_id')
                ->nullable()
                ->constrained('kich_co')
                ->nullOnDelete();
            $table->string('ten_san_pham', 150);
            $table->string('ten_kich_co', 50)->nullable();
            $table->decimal('don_gia', 12, 2);
            $table->integer('so_luong');
            $table->text('ghi_chu_mon')->nullable();
            $table->decimal('thanh_tien', 12, 2);
            $table->enum('loai_don', ['đặt hàng trước', 'sử dụng ngay'])->default('sử dụng ngay');
            $table->enum('trang_thai_thanh_toan', ['chưa thanh toán', 'đã thanh toán'])->default('chưa thanh toán');
            $table->enum('phuong_thuc_thanh_toan', ['tiền mặt', 'chuyển khoản'])->nullable();
            $table->float('so_tien_giam')->default(0);
            $table->float('tong_tien')->default(0);
            $table->dateTime('thoi_gian_den')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['don_hang_id', 'san_pham_id']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hang');
    }
};
