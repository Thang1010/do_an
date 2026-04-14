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
        Schema::create('hinh_anh_san_pham', function (Blueprint $table) {
            $table->id();
            $table->foreignId('san_pham_id')
                ->constrained('san_pham')
                ->cascadeOnDelete();
            $table->string('duong_dan_anh');
            $table->boolean('la_anh_chinh')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('san_pham_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hinh_anh_san_pham');
    }
};
