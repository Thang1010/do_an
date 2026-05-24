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
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('ca_lam_viec_id')->constrained('ca_lam_viec')->cascadeOnDelete();
            $table->dateTime('check_in_luc')->nullable();
            $table->dateTime('check_out_luc')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index(['nguoi_dung_id', 'ca_lam_viec_id']);
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
