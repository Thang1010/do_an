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
        Schema::create('ho_so_nhan_vien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')
                ->unique()
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();
            $table->string('ma_nhan_vien', 50)->unique();
            $table->string('chuc_vu', 100)->nullable();
            $table->decimal('luong_co_ban', 12, 2)->default(0);
            $table->date('ngay_vao_lam')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_so_nhan_vien');
    }
};
