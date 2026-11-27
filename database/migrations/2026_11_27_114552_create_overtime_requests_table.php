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
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type')->default('WITH_LETTER'); // WITH_LETTER (SPTPL), WITHOUT_LETTER
            $table->foreignId('overtime_master_order_id')->nullable()->constrained('overtime_master_orders')->nullOnDelete();
            $table->unsignedSmallInteger('letter_year')->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->string('letter_number')->nullable()->unique();
            $table->string('draft_code')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('letter_date');
            $table->string('day_type')->default('WORKDAY'); // WORKDAY, HOLIDAY, MIXED
            $table->string('signer_name')->default('Pak Dedi');
            $table->string('signer_title')->default('Kepala Bagian Umum');
            $table->string('signer_nip')->nullable();
            $table->string('attachment_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->json('imported_pdf_summary')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, SUBMITTED, RETURNED, APPROVED, REJECTED, CANCELLED
            $table->foreignId('proposer_id')->constrained('users');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('approver_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('replaces_overtime_request_id')->nullable()->constrained('overtime_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'request_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
