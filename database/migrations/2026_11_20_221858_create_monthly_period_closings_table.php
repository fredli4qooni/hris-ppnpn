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
        Schema::create('monthly_period_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->boolean('is_locked')->default(true);
            $table->foreignId('closed_by_admin_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('closed_at');
            $table->unsignedInteger('total_employees_processed')->default(0);
            $table->decimal('total_base_salary', 15, 2)->default(0.00);
            $table->decimal('total_deductions_amount', 15, 2)->default(0.00);
            $table->decimal('total_net_salary', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('reopened_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_period_closings');
    }
};
