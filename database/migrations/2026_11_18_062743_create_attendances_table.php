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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->foreignId('office_location_id')->nullable()->constrained('office_locations')->nullOnDelete();
            $table->date('date');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->decimal('check_in_distance_meters', 8, 2)->nullable();
            $table->decimal('check_out_distance_meters', 8, 2)->nullable();
            $table->string('check_in_photo_path')->nullable();
            $table->string('check_out_photo_path')->nullable();
            $table->string('status', 30)->default('ON_TIME'); // ON_TIME, TOLERANCE, LATE_TL, EARLY_LEAVE, ALPHA
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('required_compensation_minutes')->default(0);
            $table->boolean('is_compensated')->default(false);
            $table->string('tl_category', 20)->default('NONE'); // NONE, TL_1, TL_2, TL_3
            $table->decimal('deduction_percentage', 5, 2)->default(0.00);
            $table->string('shift_type', 30)->default('NORMAL'); // NORMAL, SECURITY_DAY, SECURITY_NIGHT
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'date', 'shift_type'], 'employee_date_shift_unique');
            $table->index(['date', 'status'], 'attendance_date_status_idx');
            $table->index(['employee_id', 'status'], 'attendance_emp_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
