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
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code')->default('WPB.0801');
            $table->string('satker_name')->default('Kantor Wilayah Direktorat Jenderal Perbendaharaan Provinsi Lampung');
            $table->string('proposer_name')->default('Mas Yudha');
            $table->string('signer_name')->default('Pak Dedi');
            $table->string('signer_title')->default('Kepala Bagian Umum');
            $table->string('signer_nip')->nullable();
            $table->unsignedInteger('spkl_sequence_start')->default(1);
            $table->unsignedInteger('sptpl_sequence_start')->default(1);
            $table->text('spkl_template_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_settings');
    }
};
