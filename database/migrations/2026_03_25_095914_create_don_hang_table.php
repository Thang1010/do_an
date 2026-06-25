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
            $table->string('email_khach_hang')->nullable();
            $table->dateTime('da_giao_luc')->nullable();

            // Mốc thời điểm nhân viên đánh dấu "đã xem/đã phục vụ" đơn khách tự gọi (QR/tài khoản).
            // NULL = chưa phục vụ → bàn rung + báo "món mới".
            $table->dateTime('da_xem_luc')->nullable();
            
            $table->timestamps();

            $table->index(['nguoi_dung_id']);
            $table->index(['created_at']);
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
