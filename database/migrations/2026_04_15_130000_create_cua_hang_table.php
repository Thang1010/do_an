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
        Schema::create('cua_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ten_cua_hang', 30);
            $table->string('so_dien_thoai', 10)->nullable();
            $table->string('dia_chi', 150)->nullable();
            $table->string('lien_ket_trang', 255)->nullable();
            $table->time('gio_mo_cua');
            $table->time('gio_dong_cua');
            $table->string('mo_ta', 150)->nullable();
            $table->timestamps();
        });

        $defaultStoreId = (int) DB::table('cua_hang')->insertGetId([
            'ten_cua_hang' => 'XM COOFEE',
            'so_dien_thoai' => null,
            'dia_chi' => null,
            'lien_ket_trang' => null,
            'gio_mo_cua' => '06:30:00',
            'gio_dong_cua' => '22:00:00',
            'mo_ta' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->foreignId('cua_hang_id')
                ->nullable()
                ->after('id')
                ->constrained('cua_hang')
                ->nullOnDelete();

            $table->index(['cua_hang_id', 'vai_tro', 'trang_thai']);
        });

        Schema::table('ho_so_quan_ly', function (Blueprint $table) {
            $table->foreignId('cua_hang_id')
                ->nullable()
                ->after('nguoi_dung_id')
                ->constrained('cua_hang')
                ->nullOnDelete();
        });

        DB::table('nguoi_dung')
            ->whereNull('cua_hang_id')
            ->update(['cua_hang_id' => $defaultStoreId]);

        DB::table('ho_so_quan_ly')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_quan_ly.nguoi_dung_id')
            ->whereNotNull('nguoi_dung.cua_hang_id')
            ->update(['ho_so_quan_ly.cua_hang_id' => DB::raw('nguoi_dung.cua_hang_id')]);

        DB::table('ho_so_quan_ly')
            ->whereNull('cua_hang_id')
            ->update(['cua_hang_id' => $defaultStoreId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ho_so_quan_ly', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cua_hang_id');
        });

        Schema::table('nguoi_dung', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cua_hang_id');
        });

        Schema::dropIfExists('cua_hang');
    }
};
