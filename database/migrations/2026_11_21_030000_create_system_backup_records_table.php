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
        Schema::create('system_backup_records', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('sha256_checksum');
            $table->string('backup_type')->default('FULL'); // FULL, DATABASE_ONLY, ATTACHMENTS_ONLY
            $table->string('database_driver')->default('mysql');
            $table->unsignedInteger('total_tables')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('backup_type');
            $table->index('is_verified');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_backup_records');
    }
};
