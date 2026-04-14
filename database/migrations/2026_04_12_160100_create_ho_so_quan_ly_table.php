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
        Schema::create('ho_so_quan_ly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')
                ->unique()
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();
            $table->string('ma_quan_ly', 50)->unique();
            $table->date('ngay_vao_lam')->nullable();
            $table->string('so_tai_khoan', 50)->nullable();
            $table->string('ngan_hang', 150)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_so_quan_ly');
    }
};
