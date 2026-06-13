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
        Schema::create('chuc_vu', function (Blueprint $table) {
            $table->id();
            $table->string('ten_chuc_vu', 40);
            $table->enum('vai_tro_ap_dung', ['nhân viên', 'quản lý'])->default('nhân viên')->index();
            $table->string('mo_ta_chuc_vu', 50)->nullable();
            $table->float('luong_co_ban')->default(0);
            $table->enum('loai_hinh_lam_viec', ['toàn thời gian', 'bán thời gian'])->nullable();
            $table->float('luong_theo_gio')->default(0);

            $table->unique(['ten_chuc_vu', 'vai_tro_ap_dung', 'loai_hinh_lam_viec'], 'chuc_vu_unique_combo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chuc_vu');
    }
};
