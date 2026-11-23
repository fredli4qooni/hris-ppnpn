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
        Schema::create('parallel_run_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('presence_date');
            $table->string('manual_check_in')->nullable();
            $table->string('manual_check_out')->nullable();
            $table->string('manual_status')->default('HADIR'); // HADIR, TERLAMBAT, IZIN, SAKIT, ALPA
            $table->string('digital_check_in')->nullable();
            $table->string('digital_check_out')->nullable();
            $table->string('digital_status')->default('HADIR'); // HADIR, TERLAMBAT, IZIN, SAKIT, ALPA
            $table->integer('time_diff_minutes')->default(0);
            $table->string('discrepancy_type')->default('MATCH'); // MATCH, TIME_DRIFT, STATUS_MISMATCH, DIGITAL_MISSING, MANUAL_MISSING
            $table->decimal('manual_penalty_rate', 5, 2)->default(0.00);
            $table->decimal('digital_penalty_rate', 5, 2)->default(0.00);
            $table->decimal('penalty_discrepancy_amount', 12, 2)->default(0.00);
            $table->decimal('concordance_score', 5, 2)->default(100.00);
            $table->string('resolution_status')->default('AUTO_MATCHED'); // AUTO_MATCHED, PENDING, RESOLVED
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'presence_date'], 'pr_recon_emp_date_unique');
            $table->index(['presence_date', 'discrepancy_type'], 'pr_recon_date_type_idx');
            $table->index('resolution_status', 'pr_recon_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parallel_run_reconciliations');
    }
};
