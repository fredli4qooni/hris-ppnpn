<?php

namespace App\Console\Commands;

use App\Services\ViolationDraftService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateViolationDraftsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hris:generate-violation-drafts {--date= : Tanggal referensi pemeriksaan (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pemeriksaan harian terotomasi untuk mendeteksi pelanggaran indisipliner dan menerbitkan draf pelanggaran SPK (FR-406 / US-503)';

    /**
     * Execute the console command.
     */
    public function handle(ViolationDraftService $draftService): int
    {
        $dateOption = $this->option('date');
        $refDate = $dateOption ? Carbon::parse($dateOption, 'Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        $this->info("Memulai pemindaian data ketidakhadiran & indisipliner pegawai per {$refDate->translatedFormat('d F Y')}...");

        $createdCount = $draftService->scanAndGenerateDrafts($refDate);

        if ($createdCount > 0) {
            $this->info("Pemindaian selesai: Berhasil menerbitkan {$createdCount} draf pelanggaran baru yang membutuhkan telaah Supervisor.");
        } else {
            $this->info('Pemindaian selesai: Tidak ditemukan kejadian indisipliner baru yang memenuhi ambang batas.');
        }

        return self::SUCCESS;
    }
}
