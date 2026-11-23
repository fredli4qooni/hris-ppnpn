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
        Schema::create('uat_test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('test_case_code')->unique();
            $table->string('module_group');
            $table->string('title');
            $table->string('actor_role');
            $table->text('scenario_description');
            $table->text('expected_result');
            $table->text('actual_result')->nullable();
            $table->string('status')->default('passed'); // passed, failed, pending, blocked
            $table->string('severity_if_failed')->nullable(); // critical, major, minor, enhancement
            $table->string('tested_by')->default('Tim Penguji UAT Gabungan');
            $table->timestamp('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('execution_duration_ms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uat_test_cases');
    }
};
