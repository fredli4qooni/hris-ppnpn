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
        Schema::create('overtime_master_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('sequence_number');
            $table->string('letter_number')->unique();
            $table->date('letter_date');
            $table->string('signer_name');
            $table->string('signer_title')->default('Kepala Bagian Umum');
            $table->string('signer_nip')->nullable();
            $table->text('template_body');
            $table->string('signed_pdf_path')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, PENDING_APPROVAL, ISSUED, CANCELLED
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_master_orders');
    }
};
