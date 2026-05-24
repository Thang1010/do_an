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
        Schema::create('bang_luong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->unsignedTinyInteger('thang');
            $table->unsignedSmallInteger('nam');
            $table->integer('tong_so_ca')->default(0);
            $table->decimal('tong_so_gio', 8, 2)->default(0);
            $table->decimal('luong_co_ban', 12, 2)->default(0);
            $table->decimal('thuong', 12, 2)->default(0);
            $table->decimal('khau_tru', 12, 2)->default(0);
            $table->decimal('luong_thuc_nhan', 12, 2)->default(0);
            $table->enum('trang_thai', ['nháp', 'đã chốt', 'đã thanh toán'])->default('nháp');
            $table->timestamps();

            $table->unique(['nguoi_dung_id', 'thang', 'nam']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bang_luong');
    }
};
