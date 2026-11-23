<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular()
                    ->disk('public'),

                TextColumn::make('employee_code')
                    ->label('NIP / Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position.name')
                    ->label('Posisi Tugas')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('supervisor.name')
                    ->label('Supervisor')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('atasan.name')
                    ->label('Pejabat Penyetuju')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('leave_quota_balance')
                    ->label('Sisa Cuti')
                    ->suffix(' hari')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('faceTemplate.id')
                    ->label('Biometrik')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->faceTemplate ? 'Wajah Terdaftar (AES-256)' : 'Belum Terdaftar')
                    ->alignCenter(),

                TextColumn::make('ews_status')
                    ->label('EWS Disiplin')
                    ->badge()
                    ->getStateUsing(fn (Employee $record): string => $record->getEwsStatus()['badge_label'])
                    ->color(fn (Employee $record): string => $record->getEwsStatus()['color'])
                    ->tooltip(fn (Employee $record): string => $record->getEwsStatus()['label'].' • Total: '.$record->calculateActiveViolationPoints().' Poin')
                    ->alignCenter(),

                TextColumn::make('contract_end_date')
                    ->label('Masa Kontrak')
                    ->formatStateUsing(fn (Employee $record): string => $record->contract_end_date ? $record->contract_end_date->format('d M Y') : '-')
                    ->description(fn (Employee $record): ?string => $record->contract_end_date ? $record->getContractStatusLabel() : null)
                    ->badge()
                    ->color(fn (Employee $record): string => $record->getContractStatusBadgeColor())
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('position_id')
                    ->label('Posisi Kerja')
                    ->relationship('position', 'name'),

                SelectFilter::make('supervisor_id')
                    ->label('Supervisor Lapangan')
                    ->options(fn () => User::role('supervisor')->pluck('name', 'id')),

                SelectFilter::make('contract_status')
                    ->label('Status Jatuh Tempo Kontrak')
                    ->options([
                        'expired' => 'Kedaluwarsa',
                        'critical' => 'Masa Kritis (H-7)',
                        'warning_30' => 'Perlu Perpanjangan (H-30)',
                        'warning_60' => 'Peringatan (H-60)',
                        'warning_90' => 'Peringatan Dini (H-90)',
                        'active' => 'Aktif Aman (> 90 hari)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        $now = Carbon::now('Asia/Jakarta')->startOfDay();

                        return match ($data['value']) {
                            'expired' => $query->where('contract_end_date', '<', $now),
                            'critical' => $query->whereBetween('contract_end_date', [$now, $now->copy()->addDays(7)]),
                            'warning_30' => $query->whereBetween('contract_end_date', [$now->copy()->addDays(8), $now->copy()->addDays(30)]),
                            'warning_60' => $query->whereBetween('contract_end_date', [$now->copy()->addDays(31), $now->copy()->addDays(60)]),
                            'warning_90' => $query->whereBetween('contract_end_date', [$now->copy()->addDays(61), $now->copy()->addDays(90)]),
                            'active' => $query->where('contract_end_date', '>', $now->copy()->addDays(90)),
                            default => $query,
                        };
                    }),

                TernaryFilter::make('is_active')
                    ->label('Status Keaktifan')
                    ->placeholder('Semua Status')
                    ->trueLabel('Hanya Pegawai Aktif')
                    ->falseLabel('Hanya Pegawai Non-Aktif'),

                TrashedFilter::make()
                    ->label('Data Terhapus'),

                SelectFilter::make('ews_status')
                    ->label('Status Early Warning System (EWS)')
                    ->options([
                        Employee::EWS_CRITICAL => 'Alarm Merah Kritis (≥ 85 Poin)',
                        Employee::EWS_WARNING => 'Peringatan Kuning (60 - 84 Poin)',
                        Employee::EWS_NORMAL => 'Disiplin Aman (< 60 Poin)',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->ewsStatus($data['value']);
                    }),
            ])
            ->recordActions([
                Action::make('enrollFace')
                    ->label('Daftar Wajah')
                    ->icon('heroicon-o-camera')
                    ->color('primary')
                    ->modalHeading(fn (Employee $record) => "Pendaftaran Biometrik Wajah: {$record->full_name} ({$record->employee_code})")
                    ->modalDescription('Perekaman sampel wajah pegawai untuk ekstraksi vektor 128 float dan enkripsi AES-256.')
                    ->modalSubmitActionLabel('Simpan Templat Biometrik')
                    ->modalWidth('lg')
                    ->form([
                        Placeholder::make('enrollment_info')
                            ->hiddenLabel()
                            ->content(fn () => view('filament.forms.components.biometric-enrollment-card')),

                        TextInput::make('sample_count')
                            ->label('Jumlah Frame Sampel Wajah')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(5)
                            ->required()
                            ->helperText('Rata-rata 3-5 frame untuk menghasilkan centroid vektor yang stabil.'),

                        Textarea::make('face_descriptor_json')
                            ->label('Vektor Deskriptor Wajah (128 Float JSON)')
                            ->helperText('Larik 128 float hasil ekstraksi model FaceAPI. Otomatis digenerasi jika menggunakan mode simulasi.')
                            ->rows(2)
                            ->extraInputAttributes(['style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; background-color: #F8FAFC; border-color: #CBD5E1; color: #334155;'])
                            ->placeholder('[0.012345, -0.045678, ... 128 nilai float]')
                            ->default(function () {
                                $service = app(BiometricVerificationService::class);
                                $sample = $service->generateMockDescriptor(0.42);

                                return json_encode($sample);
                            })
                            ->required(),
                    ])
                    ->action(function (Employee $record, array $data, BiometricVerificationService $service): void {
                        $rawJson = $data['face_descriptor_json'];
                        $decoded = json_decode($rawJson, true);

                        if (! is_array($decoded) || count($decoded) !== BiometricVerificationService::VECTOR_DIMENSION) {
                            Notification::make()
                                ->title('Vektor Wajah Tidak Valid')
                                ->body('Vektor deskriptor harus berupa JSON array tepat 128 nilai float.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $sampleCount = (int) ($data['sample_count'] ?? 3);
                        $sampleFrames = [];
                        for ($i = 0; $i < $sampleCount; $i++) {
                            // Slight variation to simulate multi-frame captures
                            $frame = array_map(fn ($val) => (float) $val + (($i * 0.001) - 0.001), $decoded);
                            $sampleFrames[] = $frame;
                        }

                        $service->enrollEmployee($record->id, $sampleFrames, Auth::id());

                        Notification::make()
                            ->title('Pendaftaran Wajah Berhasil')
                            ->body("Templat biometrik {$record->full_name} berhasil disimpan dalam format terenkripsi AES-256 (Kepatuhan UU PDP No. 27/2022).")
                            ->success()
                            ->send();
                    }),

                Action::make('verifyFace')
                    ->label('Uji Biometrik')
                    ->icon('heroicon-o-finger-print')
                    ->color('info')
                    ->visible(fn (Employee $record): bool => $record->hasFaceEnrolled())
                    ->modalHeading(fn (Employee $record) => "Uji Verifikasi Biometrik: {$record->full_name}")
                    ->modalDescription('Uji kecocokan probe sampel wajah terhadap templat terenkripsi di database (Ambang batas toleransi Euclidean Distance <= 0.40).')
                    ->modalSubmitActionLabel('Jalankan Verifikasi')
                    ->modalWidth('lg')
                    ->form([
                        Placeholder::make('verify_info')
                            ->hiddenLabel()
                            ->content(fn () => view('filament.forms.components.biometric-verify-card')),

                        Textarea::make('probe_vector_json')
                            ->label('Vektor Wajah Probe (128 Float)')
                            ->rows(2)
                            ->extraInputAttributes(['style' => 'font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; background-color: #F8FAFC; border-color: #CBD5E1; color: #334155;'])
                            ->required()
                            ->default(function (Employee $record) {
                                if ($record->faceTemplate) {
                                    $vector = $record->faceTemplate->getDecryptedVector();

                                    return json_encode($vector);
                                }

                                return '[]';
                            })
                            ->helperText('Nilai vektor wajah uji coba yang akan dicocokkan terhadap templat pegawai.'),
                    ])
                    ->action(function (Employee $record, array $data, BiometricVerificationService $service): void {
                        $decoded = json_decode($data['probe_vector_json'], true);

                        if (! is_array($decoded) || count($decoded) !== BiometricVerificationService::VECTOR_DIMENSION) {
                            Notification::make()
                                ->title('Vektor Probe Tidak Valid')
                                ->body('Vektor probe harus berupa JSON array tepat 128 nilai float.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $template = $record->faceTemplate;
                        if (! $template) {
                            return;
                        }

                        $result = $service->verifyFace($decoded, $template->encrypted_vector);

                        if ($result['is_match']) {
                            Notification::make()
                                ->title('Verifikasi Wajah COCOK (MATCH)')
                                ->body("Jarak Euclidean: {$result['distance']} (Batas: {$result['threshold']}). Tingkat kemiripan: {$result['similarity_percentage']}%.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Verifikasi Wajah GAGAL (MISMATCH)')
                                ->body("Jarak Euclidean: {$result['distance']} melebihi ambang batas toleransi {$result['threshold']}. Wajah ditolak.")
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('extendContract')
                    ->label('Perpanjang Kontrak')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->modalHeading(fn (Employee $record) => "Perpanjangan Kontrak / Adendum SPK: {$record->full_name}")
                    ->modalDescription('Perpanjang masa berlaku kontrak kerja pegawai tanpa menghilangkan atau mengubah riwayat absensi dan pelanggaran sebelumnya.')
                    ->modalSubmitActionLabel('Simpan Perpanjangan SPK')
                    ->form([
                        TextInput::make('spk_number')
                            ->label('Nomor SPK / Adendum Baru')
                            ->default(fn (Employee $record) => $record->spk_number ?? 'SPK-20/WPB.08/PPK/2025')
                            ->required(),

                        DatePicker::make('contract_start_date')
                            ->label('Tanggal Mulai Kontrak Baru')
                            ->default(fn (Employee $record) => $record->contract_end_date ? $record->contract_end_date->copy()->addDay() : now())
                            ->required(),

                        DatePicker::make('contract_end_date')
                            ->label('Tanggal Berakhir Kontrak Baru')
                            ->default(fn (Employee $record) => $record->contract_end_date ? $record->contract_end_date->copy()->addYear() : now()->addYear())
                            ->required(),

                        TextInput::make('base_salary')
                            ->label('Honorarium Bulanan Baru (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Employee $record) => $record->base_salary)
                            ->required(),

                        TextInput::make('contract_value')
                            ->label('Nilai Total Kontrak Baru (Opsional)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Employee $record) => $record->contract_value),

                        Textarea::make('notes')
                            ->label('Catatan Adendum / SK Perpanjangan')
                            ->placeholder('Contoh: Adendum Perpanjangan Kontrak PPNPN Tahun Anggaran 2026/2027'),
                    ])
                    ->action(function (Employee $record, array $data): void {
                        /** @var User|null $user */
                        $user = Auth::user();

                        $record->extendContract(
                            $data['spk_number'],
                            $data['contract_start_date'],
                            $data['contract_end_date'],
                            ! empty($data['base_salary']) ? (float) $data['base_salary'] : null,
                            ! empty($data['contract_value']) ? (float) $data['contract_value'] : null,
                            $data['notes'] ?? null,
                            $user
                        );

                        Notification::make()
                            ->title('Kontrak Berhasil Diperpanjang')
                            ->body("Masa berlaku kontrak {$record->full_name} diperpanjang hingga {$record->contract_end_date?->format('d M Y')}.")
                            ->success()
                            ->send();
                    })
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return (bool) $user?->hasRole('admin');
                    }),

                Action::make('disciplineSummary')
                    ->label('Berita Acara')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color(fn (Employee $record) => $record->getEwsStatus()['is_critical'] ? 'danger' : ($record->getEwsStatus()['is_warning'] ? 'warning' : 'primary'))
                    ->tooltip('Unduh / Cetak Berita Acara Rekam Jejak Pelanggaran PPNPN')
                    ->url(fn (Employee $record) => route('admin.employees.discipline-summary', $record))
                    ->openUrlInNewTab(),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
