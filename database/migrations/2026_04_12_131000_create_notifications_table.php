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
        Schema::create('thong_bao', function (Blueprint $table) {
            $table->uuid('ma_thong_bao')->primary();
            $table->string('loai');
            $table->unsignedBigInteger('doi_tuong_id');
            $table->string('doi_tuong_loai');
            $table->text('du_lieu');
            $table->timestamp('da_doc_luc')->nullable();
            $table->timestamps();

            $table->index(['doi_tuong_id', 'doi_tuong_loai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thong_bao');
    }
};
