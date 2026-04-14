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
        Schema::create('lich_su_goi_ai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_chat_id')->constrained('phien_chat')->cascadeOnDelete();
            $table->string('ten_mo_hinh', 100);
            $table->longText('noi_dung_gui_di')->nullable();
            $table->longText('noi_dung_nhan_ve')->nullable();
            $table->integer('token_dau_vao')->default(0);
            $table->integer('token_dau_ra')->default(0);
            $table->integer('tong_token')->default(0);
            $table->decimal('chi_phi_uoc_tinh', 12, 4)->default(0);
            $table->enum('trang_thai', ['thành công', 'thất bại'])->default('thành công');
            $table->text('thong_bao_loi')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phien_chat_id', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_goi_ai');
    }
};
