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
        Schema::create('violation_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('sanction_type', 50)->index();
            $table->integer('total_points_snapshot')->default(0);
            $table->decimal('deduction_percentage', 5, 2)->default(0.00);
            $table->integer('duration_months')->default(0);
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->date('issued_date');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_termination_recommended')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_sanctions');
    }
};
