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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('leave_type', 30); // ANNUAL_LEAVE, SICK_LEAVE, SPECIAL_LEAVE, DUTY_LEAVE, EARLY_DEPARTURE
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('total_days')->default(1);
            $table->text('reason');
            $table->string('attachment_path')->nullable();

            // Tier 1: Supervisor Field Verification
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supervisor_status', 20)->default('PENDING'); // PENDING, VERIFIED, REJECTED
            $table->text('supervisor_notes')->nullable();
            $table->dateTime('supervisor_action_at')->nullable();

            // Tier 2: Final Legal Approval by Atasan / PPK
            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('PENDING_SUPERVISOR'); // PENDING_SUPERVISOR, PENDING_ATASAN, APPROVED, REJECTED, CANCELLED
            $table->text('atasan_notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status'], 'leave_req_emp_status_idx');
            $table->index(['supervisor_id', 'supervisor_status'], 'leave_req_sup_status_idx');
            $table->index(['atasan_id', 'status'], 'leave_req_atasan_status_idx');
            $table->index(['start_date', 'end_date'], 'leave_req_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
