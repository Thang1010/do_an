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
        Schema::create('tai_khoan_cho_xac_minh', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten', 150);
            $table->string('email', 150)->unique();
            $table->string('mat_khau_ma_hoa', 255);
            $table->string('ma_xac_minh_ma_hoa', 255);
            $table->timestamp('het_han_luc')->nullable();
            $table->timestamps();

            $table->index(['email', 'het_han_luc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tai_khoan_cho_xac_minh');
    }
};
