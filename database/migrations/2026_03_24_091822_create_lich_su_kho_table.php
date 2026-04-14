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
        Schema::create('lich_su_kho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguyen_lieu_id')->constrained('nguyen_lieu')->cascadeOnDelete();
            $table->enum('loai_giao_dich', ['nhập kho', 'xuất kho', 'điều chỉnh']);
            $table->decimal('so_luong', 12, 2);
            $table->decimal('don_gia', 12, 2)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->foreignId('nguoi_tao_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['nguyen_lieu_id', 'loai_giao_dich']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_kho');
    }
};
