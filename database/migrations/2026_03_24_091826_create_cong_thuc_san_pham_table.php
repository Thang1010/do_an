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
        Schema::create('cong_thuc_san_pham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->foreignId('nguyen_lieu_id')->constrained('nguyen_lieu')->cascadeOnDelete();
            $table->decimal('so_luong_can', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['san_pham_id', 'nguyen_lieu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cong_thuc_san_pham');
    }
};
