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
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->id();
            $table->string('email', 60)->unique();
            $table->string('google_id', 191)->nullable()->unique();
            $table->string('mat_khau')->nullable();
            $table->enum('vai_tro', ['khách hàng', 'nhân viên', 'quản lý', 'chủ cửa hàng'])
                ->default('khách hàng');
            $table->enum('trang_thai', ['hoạt động', 'ngưng hoạt động'])
                ->default('hoạt động');
            $table->timestamp('email_da_xac_thuc_luc')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['vai_tro', 'trang_thai']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung');
    }
};
