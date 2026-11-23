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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('employee_code', 50)->unique();
            $table->string('full_name', 150);
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('phone_number', 25)->nullable();
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->unsignedSmallInteger('leave_quota_balance')->default(12);
            $table->string('photo_path')->nullable();
            $table->string('spk_number', 100)->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('registered_device_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
