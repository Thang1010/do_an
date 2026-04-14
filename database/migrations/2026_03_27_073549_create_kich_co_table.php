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
            $table->string('ten_kich_co', 50);
            $table->string('ma_kich_co', 20)->nullable()->unique();
            $table->text('mo_ta')->nullable();
            $table->timestamps();
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
