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
        Schema::create('voucher_nguoi_dung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained('voucher')->cascadeOnDelete();
            $table->enum('trang_thai', ['chưa dùng', 'đã dùng', 'đã hết hạn'])->default('chưa dùng');
            $table->dateTime('duoc_cap_luc')->useCurrent();
            $table->dateTime('da_dung_luc')->nullable();

            $table->unique(['nguoi_dung_id', 'voucher_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_nguoi_dung');
    }
};
