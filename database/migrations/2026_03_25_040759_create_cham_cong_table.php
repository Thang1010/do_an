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
        Schema::create('cham_cong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nhan_vien_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('phan_cong_ca_id')->constrained('phan_cong_ca')->cascadeOnDelete();
            $table->dateTime('check_in_luc')->nullable();
            $table->dateTime('check_out_luc')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index(['nhan_vien_id', 'phan_cong_ca_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cham_cong');
    }
};
