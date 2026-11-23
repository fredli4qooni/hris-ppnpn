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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_out_status', 30)->nullable()->after('status'); // ON_TIME, LUNAS_PENGGANTIAN, TIDAK_MENGGANTI_JAM, PSW
            $table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('required_compensation_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_out_status', 'early_leave_minutes']);
        });
    }
};
