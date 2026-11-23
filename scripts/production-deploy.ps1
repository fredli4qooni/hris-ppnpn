# ==============================================================================
# SKRIP DEPLOYMENT OTOMATISASI LINGKUNGAN PRODUKSI (US-804 / GO-LIVE READINESS)
# KANWIL DITJEN PERBENDAHARAAN PROVINSI LAMPUNG
# Aplikasi Sistem Presensi & Penggajian PPNPN Terpadu
# ==============================================================================

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$ErrorActionPreference = "Stop"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "🚀 MEMULAI DEPLOYMENT PRODUKSI HRIS PPNPN KANWIL DJPB LAMPUNG" -ForegroundColor Yellow
Write-Host "=================================================================" -ForegroundColor Cyan

# 1. Aktifkan Maintenance Mode
Write-Host "`n[1/8] Mengaktifkan Maintenance Mode..." -ForegroundColor Magenta
php artisan down --secret="djpb-deploy-bypass" --render="errors.503" 2>$null
if ($LASTEXITCODE -ne 0) {
    php artisan down --secret="djpb-deploy-bypass"
}
Write-Host "✔ Aplikasi berada dalam status Maintenance Mode (Bypass Token: djpb-deploy-bypass)." -ForegroundColor Green

# 2. Instalasi Dependency Composer Produksi
Write-Host "`n[2/8] Mengoptimalkan paket dependensi Composer (No-Dev)..." -ForegroundColor Magenta
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
Write-Host "✔ Composer dependencies teroptimasi penuh." -ForegroundColor Green

# 3. Jalankan Migrasi Basis Data
Write-Host "`n[3/8] Menjalankan migrasi skema tabel basis data produksi..." -ForegroundColor Magenta
php artisan migrate --force --no-interaction
Write-Host "✔ Skema database produksi terbarui." -ForegroundColor Green

# 4. Optimasi Caching Framework
Write-Host "`n[4/8] Mengompilasi dan mengunci cache konfigurasi, routes, views, dan events..." -ForegroundColor Magenta
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
Write-Host "✔ Cache framework terkompilasi optimal." -ForegroundColor Green

# 5. Tautan Simbolik Storage
Write-Host "`n[5/8] Memverifikasi symbolic link berkas penyimpanan publik..." -ForegroundColor Magenta
php artisan storage:link 2>$null
Write-Host "✔ Storage link siap." -ForegroundColor Green

# 6. Bersihkan dan Muat Ulang Cache Aplikasi
Write-Host "`n[6/8] Memperbarui cache aplikasi..." -ForegroundColor Magenta
php artisan cache:clear
Write-Host "✔ Cache aplikasi bersih." -ForegroundColor Green

# 7. Muat Ulang Background Queue Worker / Scheduler
Write-Host "`n[7/8] Me-restart worker antrean background..." -ForegroundColor Magenta
php artisan queue:restart
Write-Host "✔ Queue workers direstart." -ForegroundColor Green

# 8. Matikan Maintenance Mode (Sistem Beroperasi Kembali)
Write-Host "`n[8/8] Membawa aplikasi kembali online (Production Live)..." -ForegroundColor Magenta
php artisan up
Write-Host "✔ Aplikasi RESMI BEROPERASI (PRODUCTION LIVE)." -ForegroundColor Green

Write-Host "`n=================================================================" -ForegroundColor Cyan
Write-Host "🎉 DEPLOYMENT PRODUKSI SELESAI DENGAN SUKSES (100% OPERATIONAL)" -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Cyan
