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
        Schema::create('nguyen_lieu', function (Blueprint $table) {
            $table->id();
            $table->string('ten_nguyen_lieu', 150);
            $table->string('don_vi_tinh', 50);
            $table->decimal('so_luong_ton', 12, 2)->default(0);
            $table->decimal('muc_canh_bao', 12, 2)->default(0);
            $table->timestamps();

            $table->index('ten_nguyen_lieu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguyen_lieu');
    }
};
