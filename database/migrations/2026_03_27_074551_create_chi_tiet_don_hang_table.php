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
        Schema::create('chi_tiet_don_hang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_hang_id')
                ->constrained('don_hang')
                ->cascadeOnDelete();
            $table->foreignId('ban_an_id')
                ->nullable()
                ->constrained('ban_an')
                ->nullOnDelete();
            $table->foreignId('san_pham_id')
                ->constrained('san_pham')
                ->restrictOnDelete();
            $table->foreignId('kich_co_id')
                ->nullable()
                ->constrained('kich_co')
                ->nullOnDelete();
            $table->string('ten_san_pham', 150);
            $table->string('ten_kich_co', 50)->nullable();
            $table->decimal('don_gia', 12, 2);
            $table->integer('so_luong');
            $table->text('ghi_chu_mon')->nullable();
            $table->decimal('thanh_tien', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['don_hang_id', 'san_pham_id']);
            $table->index(['ban_an_id', 'san_pham_id']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hang');
    }
};
