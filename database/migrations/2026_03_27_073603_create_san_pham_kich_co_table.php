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
        Schema::create('san_pham_kich_co', function (Blueprint $table) {
            $table->id();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->foreignId('kich_co_id')->constrained('kich_co')->cascadeOnDelete();
            $table->decimal('gia_ban', 12, 2);
            $table->decimal('gia_khuyen_mai', 12, 2)->nullable();
            $table->enum('trang_thai', ['đang bán', 'ngừng bán'])->default('đang bán');
            $table->timestamps();

            $table->unique(['san_pham_id', 'kich_co_id']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('san_pham_kich_co');
    }
};
