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
        Schema::create('phan_cong_ca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nhan_vien_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('ca_lam_viec_id')->constrained('ca_lam_viec')->cascadeOnDelete();
            $table->date('ngay_lam');
            $table->enum('trang_thai', ['đã phân công', 'đã hoàn thành', 'vắng mặt'])->default('đã phân công');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['nhan_vien_id', 'ca_lam_viec_id', 'ngay_lam'], 'unique_phan_cong_ca');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phan_cong_ca');
    }
};
