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
            $table->boolean('is_dispensation')->default(false)->after('shift_type');
            $table->boolean('tl_deduction_exempted')->default(false)->after('is_dispensation');
            $table->text('dispensation_notes')->nullable()->after('tl_deduction_exempted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_dispensation', 'tl_deduction_exempted', 'dispensation_notes']);
        });
    }
};
