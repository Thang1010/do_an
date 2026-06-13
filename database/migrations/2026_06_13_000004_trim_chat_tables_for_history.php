<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phien_chat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ban_an_id');
            $table->dropUnique(['ma_phien']);
            $table->dropColumn(['ma_phien', 'tieu_de', 'bat_dau_luc', 'ket_thuc_luc']);
        });

        // Rút gọn enum kenh_chat: bỏ giá trị 'hỗ trợ nhân viên' (không dùng)
        DB::statement("ALTER TABLE phien_chat MODIFY kenh_chat ENUM('website khách vãng lai', 'website khách hàng') NOT NULL DEFAULT 'website khách vãng lai'");

        Schema::table('tin_nhan_chat', function (Blueprint $table) {
            $table->dropColumn(['loai_tin_nhan', 'y_dinh', 'so_token', 'thoi_gian_phan_hoi_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phien_chat', function (Blueprint $table) {
            $table->foreignId('ban_an_id')->nullable()->after('kenh_chat')->constrained('ban_an')->nullOnDelete();
            $table->string('ma_phien', 100)->nullable()->after('nguoi_dung_id');
            $table->string('tieu_de')->nullable()->after('ban_an_id');
            $table->dateTime('bat_dau_luc')->nullable()->after('tieu_de');
            $table->dateTime('ket_thuc_luc')->nullable()->after('bat_dau_luc');
        });

        DB::statement("ALTER TABLE phien_chat MODIFY kenh_chat ENUM('website khách vãng lai', 'website khách hàng', 'hỗ trợ nhân viên') NOT NULL DEFAULT 'website khách vãng lai'");

        Schema::table('tin_nhan_chat', function (Blueprint $table) {
            $table->enum('loai_tin_nhan', ['văn bản', 'gợi ý sản phẩm', 'ngữ cảnh thời tiết', 'hỗ trợ đơn hàng'])
                ->default('văn bản')->after('noi_dung');
            $table->string('y_dinh', 100)->nullable()->after('loai_tin_nhan');
            $table->integer('so_token')->default(0)->after('y_dinh');
            $table->integer('thoi_gian_phan_hoi_ms')->nullable()->after('so_token');
        });
    }
};
