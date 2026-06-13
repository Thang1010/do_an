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
        Schema::create('thanh_toan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')->constrained('don_hang')->cascadeOnDelete();
            $table->string('ma_thanh_toan', 100)->nullable();
            $table->string('phuong_thuc', 50)->nullable();
            $table->decimal('so_tien', 12, 2)->default(0);
            $table->string('trang_thai', 50)->nullable();
            $table->string('noi_dung_chuyen_khoan', 255)->nullable();
            $table->string('duong_dan_qr', 255)->nullable();
            $table->string('ma_giao_dich', 100)->nullable();
            $table->timestamp('thanh_toan_luc')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index(['don_hang_id', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thanh_toan');
    }
};
