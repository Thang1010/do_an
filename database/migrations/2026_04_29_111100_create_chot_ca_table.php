<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chot_ca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ca_lam_viec_id')->constrained('ca_lam_viec')->cascadeOnDelete();
            $table->foreignId('nguoi_chot_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->decimal('so_tien_dau_ca', 12, 2)->default(0);
            $table->timestamp('chot_luc')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->unique('ca_lam_viec_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chot_ca');
    }
};
