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
            $table->foreignId('chuc_vu_id')->nullable()->constrained('chuc_vu')->nullOnDelete();
            $table->string('ho_ten', 70)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('dia_chi_tam_chu', 150)->nullable();
            $table->string('so_dien_thoai', 10)->nullable();
            $table->date('ngay_vao_lam')->nullable();
            $table->string('anh_dai_dien')->nullable();
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
