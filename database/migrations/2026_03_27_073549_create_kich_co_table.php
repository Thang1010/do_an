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
        Schema::create('kich_co', function (Blueprint $table) {
            $table->id();
            $table->foreignId('san_pham_id')
                ->nullable()
                ->constrained('san_pham')
                ->cascadeOnDelete();
            $table->string('ten_kich_co', 50);
            $table->string('ma_kich_co', 20)->nullable();
            $table->float('he_so_gia')->default(0);
            $table->string('mo_ta', 50)->nullable();
            $table->timestamps();

            $table->unique(['san_pham_id', 'ma_kich_co']);
            $table->index('san_pham_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kich_co');
    }
};
