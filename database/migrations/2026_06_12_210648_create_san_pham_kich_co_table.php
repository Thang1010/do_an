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
        // 1. Tạo bảng trung gian san_pham_kich_co
        Schema::create('san_pham_kich_co', function (Blueprint $table) {
            $table->id();
            $table->foreignId('san_pham_id')->constrained('san_pham')->cascadeOnDelete();
            $table->foreignId('kich_co_id')->constrained('kich_co')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['san_pham_id', 'kich_co_id']);
        });

        // 2. Chuyển đổi dữ liệu cũ
        // Nhóm các kích cỡ có cùng tên lại để lấy 1 master ID
        $sizes = DB::table('kich_co')->get();
        $masters = [];

        foreach ($sizes as $size) {
            if (!isset($masters[$size->ten_kich_co])) {
                // Giữ lại dòng đầu tiên làm master
                $masters[$size->ten_kich_co] = $size->id;
            }
            
            $masterId = $masters[$size->ten_kich_co];

            // Nếu sản phẩm ID có tồn tại, đưa vào bảng trung gian
            if ($size->san_pham_id) {
                DB::table('san_pham_kich_co')->insert([
                    'san_pham_id' => $size->san_pham_id,
                    'kich_co_id' => $masterId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Đổi ID ở chi tiết đơn hàng sang master ID (nếu có)
            DB::table('chi_tiet_don_hang')
                ->where('kich_co_id', $size->id)
                ->update(['kich_co_id' => $masterId]);
        }

        // Xóa các dòng kich_co không phải master
        $masterIds = array_values($masters);
        if (!empty($masterIds)) {
            DB::table('kich_co')->whereNotIn('id', $masterIds)->delete();
        } else {
            DB::table('kich_co')->truncate();
        }

        // 3. Drop khóa ngoại và cột ở bảng kich_co
        Schema::table('kich_co', function (Blueprint $table) {
            // Drop unique constraint first
            $table->dropUnique(['san_pham_id', 'ma_kich_co']);
            $table->dropForeign(['san_pham_id']);
            $table->dropColumn(['san_pham_id']);
            // Add a unique constraint to ma_kich_co so it acts as a proper master dictionary
            $table->unique('ma_kich_co');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kich_co', function (Blueprint $table) {
            $table->dropUnique(['ma_kich_co']);
            $table->foreignId('san_pham_id')->nullable()->constrained('san_pham')->cascadeOnDelete();

            $table->unique(['san_pham_id', 'ma_kich_co']);
        });

        // Copy back from pivot
        $pivotData = DB::table('san_pham_kich_co')->get();
        foreach ($pivotData as $pivot) {
            // This is just a basic fallback.
            $master = DB::table('kich_co')->where('id', $pivot->kich_co_id)->first();
            if ($master) {
                DB::table('kich_co')->insert([
                    'san_pham_id' => $pivot->san_pham_id,
                    'ten_kich_co' => $master->ten_kich_co,
                    'ma_kich_co' => $master->ma_kich_co,

                    'mo_ta' => $master->mo_ta,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        Schema::dropIfExists('san_pham_kich_co');
    }
};
