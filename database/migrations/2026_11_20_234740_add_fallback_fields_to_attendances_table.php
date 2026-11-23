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
            $table->boolean('is_fallback')->default(false)->after('shift_type');
            $table->string('fallback_reason')->nullable()->after('is_fallback');
            $table->string('fallback_status', 30)->default('NONE')->after('fallback_reason'); // NONE, PENDING, APPROVED, REJECTED
            $table->foreignId('fallback_verified_by')->nullable()->after('fallback_status')->constrained('users')->nullOnDelete();
            $table->timestamp('fallback_verified_at')->nullable()->after('fallback_verified_by');
            $table->text('fallback_notes')->nullable()->after('fallback_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['fallback_verified_by']);
            $table->dropColumn([
                'is_fallback',
                'fallback_reason',
                'fallback_status',
                'fallback_verified_by',
                'fallback_verified_at',
                'fallback_notes',
            ]);
        });
    }
};
