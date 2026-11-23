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
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('correction_type', 30)->default('BOTH'); // CHECK_IN, CHECK_OUT, BOTH
            $table->time('suggested_check_in_at')->nullable();
            $table->time('suggested_check_out_at')->nullable();
            $table->text('work_summary');
            $table->string('proof_document_path')->nullable();
            $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED_SUPERVISOR, APPROVED, REJECTED
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atasan_decided_at')->nullable();
            $table->text('atasan_notes')->nullable();
            $table->foreignId('created_attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'attendance_date'], 'att_corr_emp_date_idx');
            $table->index(['status'], 'att_corr_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
