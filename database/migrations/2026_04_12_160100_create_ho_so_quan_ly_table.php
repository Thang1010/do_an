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
        Schema::create('ho_so_quan_ly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_dung_id')
                ->unique()
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();
            $table->foreignId('chuc_vu_id')
                ->nullable()
                ->constrained('chuc_vu')
                ->nullOnDelete();
            $table->string('ma_quan_ly', 50)->unique();
            $table->string('loai_hinh_lam_viec', 30)->default('toàn thời gian');
            $table->decimal('luong_co_ban', 12, 2)->nullable();
            $table->decimal('luong_theo_gio', 12, 2)->nullable();
            $table->date('ngay_vao_lam')->nullable();
            $table->timestamps();
        });

        // Ensure "Quản lý" position exists
        $managerPositionId = DB::table('chuc_vu')->where('ten_chuc_vu', 'Quản lý')->value('id');

        if (! $managerPositionId) {
            $managerPositionId = DB::table('chuc_vu')->insertGetId([
                'ten_chuc_vu' => 'Quản lý',
                'mo_ta_chuc_vu' => 'Chức vụ dành cho tài khoản quản lý.',
            ]);
        }

        // Assign manager position to existing records
        DB::table('ho_so_quan_ly')
            ->whereNull('chuc_vu_id')
            ->update(['chuc_vu_id' => $managerPositionId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ho_so_quan_ly');
    }
};
