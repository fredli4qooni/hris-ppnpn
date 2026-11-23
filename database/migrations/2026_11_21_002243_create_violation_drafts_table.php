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
        Schema::create('violation_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('violation_catalog_id')->nullable()->constrained('violation_catalogs')->nullOnDelete();
            $table->string('trigger_type', 50)->index(); // CONSECUTIVE_ALPHA, REJECTED_ATTENDANCE_CORRECTION, REJECTED_FALLBACK, REPEATED_PSW
            $table->date('incident_date')->index();
            $table->text('auto_generated_notes');
            $table->json('evidence_data')->nullable();
            $table->string('status', 30)->default('PENDING')->index(); // PENDING, CONFIRMED, DISMISSED

            // Confirmation by Supervisor
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('employee_violation_id')->nullable()->constrained('employee_violations')->nullOnDelete();

            // Dismissal by Supervisor
            $table->foreignId('dismissed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->text('dismissal_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_drafts');
    }
};
