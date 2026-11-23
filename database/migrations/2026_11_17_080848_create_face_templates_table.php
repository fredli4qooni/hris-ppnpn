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
        Schema::create('face_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->longText('encrypted_vector')->comment('AES-256 encrypted 128-float face descriptor vector (UU PDP compliant)');
            $table->unsignedSmallInteger('sample_count')->default(3)->comment('Number of face frames captured for centroid vector');
            $table->string('model_version', 50)->default('vladmandic-face-api-v1');
            $table->foreignId('enrolled_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_templates');
    }
};
