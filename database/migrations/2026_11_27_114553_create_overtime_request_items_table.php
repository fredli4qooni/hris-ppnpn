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
        Schema::create('overtime_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('overtime_request_id')->constrained('overtime_requests')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('start_time', 5); // HH:MM
            $table->string('end_time', 5);   // HH:MM
            $table->unsignedInteger('duration_minutes');
            $table->decimal('duration_hours', 5, 2);
            $table->text('description');
            $table->timestamps();

            $table->unique(['overtime_request_id', 'employee_id'], 'overtime_req_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_request_items');
    }
};
