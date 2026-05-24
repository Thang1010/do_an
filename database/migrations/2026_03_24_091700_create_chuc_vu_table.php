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
        Schema::create('chuc_vu', function (Blueprint $table) {
            $table->id();
            $table->string('ten_chuc_vu', 100)->unique();
            $table->string('vai_tro_ap_dung', 20)->default('nhân viên')->index();
            $table->text('mo_ta_chuc_vu')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chuc_vu');
    }
};
