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
        Schema::create('voucher', function (Blueprint $table) {
            $table->id();
            $table->string('ma_voucher', 50)->unique();
            $table->string('ten_voucher', 150);
            $table->enum('loai_giam', ['phần trăm', 'tiền mặt']);
            $table->decimal('gia_tri_giam', 12, 2);
            $table->decimal('don_toi_thieu', 12, 2)->default(0);
            $table->decimal('giam_toi_da', 12, 2)->nullable();
            $table->integer('so_luong')->default(0);
            $table->integer('da_su_dung')->default(0);
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->enum('trang_thai', ['đang hoạt động', 'ngưng hoạt động', 'hết hạn'])->default('đang hoạt động');
            $table->timestamps();

            $table->index(['ma_voucher', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};
