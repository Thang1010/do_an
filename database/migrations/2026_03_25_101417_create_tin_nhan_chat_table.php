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
        Schema::create('tin_nhan_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_chat_id')->constrained('phien_chat')->cascadeOnDelete();
            $table->enum('nguoi_gui', ['người dùng', 'chatbot']);
            $table->longText('noi_dung');
            $table->enum('loai_tin_nhan', ['văn bản', 'gợi ý sản phẩm', 'ngữ cảnh thời tiết', 'hỗ trợ đơn hàng'])
                ->default('văn bản');
            $table->string('y_dinh', 100)->nullable();
            $table->integer('so_token')->default(0);
            $table->integer('thoi_gian_phan_hoi_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phien_chat_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tin_nhan_chat');
    }
};
