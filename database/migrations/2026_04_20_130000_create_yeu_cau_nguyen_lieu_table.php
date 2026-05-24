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
        Schema::create('yeu_cau_nguyen_lieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cua_hang_id')->nullable()->constrained('cua_hang')->nullOnDelete();
            $table->foreignId('nguoi_gui_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('nguoi_duyet_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->enum('trang_thai', ['cho_xac_nhan', 'da_duyet', 'tu_choi'])->default('cho_xac_nhan');
            $table->json('du_lieu');
            $table->text('ghi_chu')->nullable();
            $table->timestamp('duyet_luc')->nullable();
            $table->timestamp('tu_choi_luc')->nullable();
            $table->timestamps();

            $table->index(['trang_thai', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_nguyen_lieu');
    }
};
