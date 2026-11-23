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
        Schema::create('bast_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('bast_number')->unique();
            $table->string('contract_number')->default('SPK-20/WPB.08/PPK/2025');
            $table->string('project_name')->default('Aplikasi Sistem Presensi & Penggajian PPNPN Terpadu');
            $table->string('work_unit')->default('Kanwil Ditjen Perbendaharaan Provinsi Lampung');
            $table->date('handover_date');

            // Pihak Pertama (Pengembang / Scrum Team)
            $table->string('first_party_name')->default('Dewi Nadila');
            $table->string('first_party_title')->default('Lead Developer / Scrum Master');
            $table->timestamp('first_party_signed_at')->nullable();

            // Pihak Kedua (Pejabat Pembuat Komitmen / Kasubbag Umum)
            $table->string('second_party_name')->default('Hendra Setiawan, S.E., M.Si.');
            $table->string('second_party_title')->default('Pejabat Pembuat Komitmen (PPK) / Kasubbag Umum');
            $table->string('second_party_nip')->default('198204152003121002');
            $table->timestamp('second_party_signed_at')->nullable();

            // Scrum Engagement Metrics
            $table->unsignedInteger('total_sprints')->default(8);
            $table->unsignedInteger('total_story_points')->default(64);
            $table->unsignedInteger('total_test_cases')->default(305);
            $table->decimal('uat_pass_rate', 5, 2)->default(100.00);
            $table->decimal('concordance_rate', 5, 2)->default(100.00);

            // Dokumen State & Checklist
            $table->string('status')->default('OFFICIALLY_SIGNED'); // DRAFT, PARTIALLY_SIGNED, OFFICIALLY_SIGNED
            $table->text('sign_notes')->nullable();
            $table->json('checklist_items')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bast_handovers');
    }
};
