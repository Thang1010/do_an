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
