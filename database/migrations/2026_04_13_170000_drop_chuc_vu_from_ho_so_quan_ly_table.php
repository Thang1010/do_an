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
        if (! Schema::hasColumn('ho_so_quan_ly', 'chuc_vu')) {
            return;
        }

        Schema::table('ho_so_quan_ly', function (Blueprint $table) {
            $table->dropColumn('chuc_vu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ho_so_quan_ly', 'chuc_vu')) {
            return;
        }

        Schema::table('ho_so_quan_ly', function (Blueprint $table) {
            $table->string('chuc_vu', 100)->nullable();
        });
    }
};
