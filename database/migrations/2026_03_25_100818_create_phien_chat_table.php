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
        Schema::create('phien_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('ma_phien', 100)->unique();
            $table->enum('kenh_chat', ['website khách vãng lai', 'website khách hàng', 'hỗ trợ nhân viên'])
                ->default('website khách vãng lai');
            $table->foreignId('ban_an_id')->nullable()->constrained('ban_an')->nullOnDelete();
            $table->string('tieu_de')->nullable();
            $table->dateTime('bat_dau_luc')->useCurrent();
            $table->dateTime('ket_thuc_luc')->nullable();
            $table->enum('trang_thai', ['đang hoạt động', 'đã đóng'])->default('đang hoạt động');
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phien_chat');
    }
};
