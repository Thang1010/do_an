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
        Schema::create('nguyen_lieu', function (Blueprint $table) {
            $table->id();
            $table->string('ten_nguyen_lieu', 40);
            $table->enum('don_vi_tinh', ['gram', 'gói', 'hộp', 'ml', 'chai']);
            $table->string('muc_dich_su_dung', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('ten_nguyen_lieu');
            $table->index('muc_dich_su_dung');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguyen_lieu');
    }
};
