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
        Schema::create('danh_gia_san_pham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->foreignId('don_hang_id')->constrained('don_hang')->cascadeOnDelete();
            $table->unsignedTinyInteger('so_sao');
            $table->text('noi_dung')->nullable();
            $table->timestamps();

            $table->index('san_pham_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_gia_san_pham');
    }
};
