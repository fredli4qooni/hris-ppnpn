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
        Schema::create('late_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('incident_date')->index();
            $table->string('category', 50)->default('OTHER_FORCE_MAJEURE'); // DISASTER, ACCIDENT, FAMILY_BEREAVEMENT, OTHER_FORCE_MAJEURE
            $table->time('incident_time')->nullable();
            $table->text('description');
            $table->string('proof_file_path')->nullable();
            $table->string('status', 30)->default('PENDING')->index(); // PENDING, APPROVED_SUPERVISOR, REJECTED_SUPERVISOR, APPROVED, REJECTED

            // Tier 1: Supervisor Field Review
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->text('supervisor_notes')->nullable();

            // Tier 2: Atasan Official Decision
            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atasan_decided_at')->nullable();
            $table->text('atasan_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_exceptions');
    }
};
