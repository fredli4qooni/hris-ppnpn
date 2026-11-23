#!/usr/bin/env bash
# ==============================================================================
# PRODUCTION DEPLOYMENT AUTOMATION SCRIPT (US-804 / GO-LIVE READINESS)
# KANWIL DITJEN PERBENDAHARAAN PROVINSI LAMPUNG
# Aplikasi Sistem Presensi & Penggajian PPNPN Terpadu
# ==============================================================================

set -e

echo "================================================================="
echo "🚀 MEMULAI DEPLOYMENT PRODUKSI HRIS PPNPN KANWIL DJPB LAMPUNG"
echo "================================================================="

# 1. Aktifkan Maintenance Mode
echo -e "\n[1/8] Mengaktifkan Maintenance Mode..."
php artisan down --secret="djpb-deploy-bypass" || true
echo "✔ Aplikasi dalam status Maintenance Mode."

# 2. Instalasi Dependency Composer Produksi
echo -e "\n[2/8] Mengoptimalkan dependensi Composer (No-Dev)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
echo "✔ Dependensi Composer berhasil dioptimalkan."

# 3. Jalankan Migrasi Basis Data
echo -e "\n[3/8] Menjalankan migrasi basis data produksi..."
php artisan migrate --force --no-interaction
echo "✔ Skema database berhasil dimigrasikan."

# 4. Optimasi Caching Framework
echo -e "\n[4/8] Mengompilasi cache konfigurasi, routes, views, dan events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo "✔ Cache framework berhasil dikompilasi."

# 5. Tautan Simbolik Storage
echo -e "\n[5/8] Memverifikasi symbolic link storage..."
php artisan storage:link || true
echo "✔ Storage link aktif."

# 6. Bersihkan Cache Aplikasi
echo -e "\n[6/8] Membersihkan cache aplikasi..."
php artisan cache:clear
echo "✔ Cache aplikasi bersih."

# 7. Muat Ulang Queue Workers
echo -e "\n[7/8] Me-restart queue workers..."
php artisan queue:restart
echo "✔ Queue workers direstart."

# 8. Matikan Maintenance Mode
echo -e "\n[8/8] Membawa aplikasi kembali online (Production Live)..."
php artisan up
echo "✔ Aplikasi RESMI BEROPERASI (PRODUCTION LIVE)."

echo -e "\n================================================================="
echo "🎉 DEPLOYMENT PRODUKSI SELESAI DENGAN SUKSES (100% OPERATIONAL)"
echo "================================================================="
