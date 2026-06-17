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
        Schema::create('phien_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->enum('kenh_chat', ['website khách vãng lai', 'website khách hàng'])
                ->default('website khách vãng lai');
            $table->enum('trang_thai', ['đang hoạt động', 'đã đóng'])->default('đang hoạt động');
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phien_chat');
    }
};
