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
            $table->string('ma_voucher', 30)->unique();
            $table->string('ten_voucher', 50)->unique();
            $table->enum('loai_giam', ['phần trăm', 'tiền mặt']);
            $table->float('gia_tri_giam')->default(0);
            $table->float('don_toi_thieu')->default(0);
            $table->float('giam_toi_da')->default(0);
            $table->integer('so_luong')->default(0);
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->enum('trang_thai', ['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động', 'hết hạn'])->default('đang hoạt động');
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
