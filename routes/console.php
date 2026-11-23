<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Daily Inactivation for Expired Contracts (FR-604 / US-604)
Schedule::command('hris:inactivate-expired-contracts')
    ->dailyAt('00:01')
    ->timezone('Asia/Jakarta')
    ->description('Penonaktifan otomatis akun pegawai yang masa kontrak kerjanya telah berakhir tanpa adendum pembaruan (FR-604)');

// Scheduled Daily Check for Contract Expiry (FR-602 / US-406)
Schedule::command('hris:check-contract-expiry')
    ->dailyAt('06:00')
    ->timezone('Asia/Jakarta')
    ->description('Pemeriksaan harian masa berlaku kontrak kerja SPK pegawai PPNPN (FR-602)');

// Scheduled Daily Scanning for Violation Drafts (FR-406 / US-503)
Schedule::command('hris:generate-violation-drafts')
    ->dailyAt('23:00')
    ->timezone('Asia/Jakarta')
    ->description('Pemeriksaan harian kejadian indisipliner untuk menerbitkan draf pelanggaran SPK (FR-406)');

// Scheduled Daily Automated System Backup & DRP (US-802)
Schedule::command('hris:backup-system')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->description('Pencadangan otomatis harian basis data dan lampiran dokumen sistem (DRP - US-802)');
