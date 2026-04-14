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
        Schema::create('danh_muc', function (Blueprint $table) {
            $table->id();
            $table->string('ten_danh_muc', 150);
            $table->string('slug', 180)->nullable()->unique();
            $table->text('mo_ta')->nullable();
            $table->enum('trang_thai', ['đang dùng', 'ngưng dùng'])->default('đang dùng');
            $table->timestamps();

            $table->index('trang_thai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_muc');
    }
};
