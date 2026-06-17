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
        Schema::create('ban_an', function (Blueprint $table) {
            $table->id();
            $table->string('so_ban', 20)->unique();
            $table->enum('trang_thai', ['trống', 'đang phục vụ', 'đã đặt', 'ngưng sử dụng'])->default('trống');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ban_an');
    }
};
