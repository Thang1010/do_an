<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ca_lam_viec_id')->constrained('ca_lam_viec')->cascadeOnDelete();
            $table->foreignId('nguoi_tao_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->foreignId('nguyen_lieu_id')->nullable()->constrained('nguyen_lieu')->cascadeOnDelete();
            $table->foreignId('lich_su_kho_id')->nullable()->constrained('lich_su_kho')->nullOnDelete();
            $table->string('phuong_thuc_thanh_toan', 50);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->index(['ca_lam_viec_id', 'phuong_thuc_thanh_toan']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tieu');
    }
};
