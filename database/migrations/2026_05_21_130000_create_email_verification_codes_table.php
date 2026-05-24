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
        Schema::create('xac_minh_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();
            $table->string('ma_xac_minh_ma_hoa', 255);
            $table->timestamp('het_han_luc')->nullable();
            $table->timestamp('xac_minh_luc')->nullable();
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'xac_minh_luc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xac_minh_email');
    }
};
