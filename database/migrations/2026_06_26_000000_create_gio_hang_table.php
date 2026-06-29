<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Giỏ hàng của KHÁCH HÀNG THÀNH VIÊN, lưu theo tài khoản để không bị mất khi
     * đăng xuất / hết phiên. Mỗi dòng = 1 món trong giỏ (liên kết sản phẩm + size).
     */
    public function up(): void
    {
        Schema::create('gio_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            // Size khách chọn — trỏ tới pivot san_pham_kich_co để đảm bảo size thuộc đúng sản phẩm.
            // Null nếu sản phẩm không có size.
            $table->foreignId('san_pham_kich_co_id')->nullable()->constrained('san_pham_kich_co')->cascadeOnDelete();
            $table->integer('so_luong')->default(1);
            $table->string('nhiet_do', 50)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index('nguoi_dung_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gio_hang');
    }
};
