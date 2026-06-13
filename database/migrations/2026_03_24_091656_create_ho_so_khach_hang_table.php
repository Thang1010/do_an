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
        Schema::create('ho_so_khach_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')
                ->unique()
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();
            $table->string('ho_ten', 70)->nullable();
            $table->enum('gioi_tinh', ['nam', 'nữ'])->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->text('dia_chi')->nullable();
            $table->string('anh_dai_dien')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_so_khach_hang');
    }
};
