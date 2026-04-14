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
        Schema::create('san_pham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('danh_muc_id')
                ->constrained('danh_muc')
                ->restrictOnDelete();
            $table->string('ten_san_pham', 150);
            $table->string('slug', 180)->nullable()->unique();
            $table->text('mo_ta')->nullable();
            $table->decimal('gia_goc', 12, 2);
            $table->decimal('gia_khuyen_mai', 12, 2)->nullable();
            $table->string('hinh_anh_chinh')->nullable();
            $table->enum('trang_thai_ban', ['đang bán', 'ngừng bán'])->default('đang bán');
            $table->enum('loai_quan_ly_kho', ['theo nguyên liệu', 'theo số lượng'])->default('theo nguyên liệu');
            $table->boolean('noi_bat')->default(false);
            $table->timestamps();

            $table->index(['danh_muc_id', 'trang_thai_ban']);
            $table->index('ten_san_pham');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('san_pham');
    }
};
