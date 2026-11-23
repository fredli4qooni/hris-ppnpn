<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class EmployeeImportService
{
    /**
     * Import employees from a file (CSV or XLSX).
     *
     * @return array{
     *     success: bool,
     *     total: int,
     *     imported: int,
     *     skipped: int,
     *     errors: list<string>
     * }
     */
    public function importFile(string $filePath, ?int $importedByUserId = null, bool $strict = true): array
    {
        $rows = $this->extractRowsFromFile($filePath);

        $total = count($rows);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $fileCodes = [];

        if ($total === 0) {
            return [
                'success' => false,
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Berkas kosong atau tidak memiliki baris data yang valid.'],
            ];
        }

        // Pass 1: Pre-validation for errors & duplicates
        foreach ($rows as $index => $cleanRow) {
            $rowNumber = $index + 2; // Accounting for 1-based index and header row

            $employeeCode = $cleanRow['employee_code'] ?? $cleanRow['nip'] ?? $cleanRow['kode_pegawai'] ?? null;
            $fullName = $cleanRow['full_name'] ?? $cleanRow['nama'] ?? $cleanRow['nama_lengkap'] ?? null;

            if (empty($employeeCode) || empty($fullName)) {
                $errors[] = "Baris {$rowNumber}: NIP / Kode Pegawai dan Nama Lengkap wajib diisi.";
                $skipped++;

                continue;
            }

            // Check duplicate in file
            if (in_array($employeeCode, $fileCodes, true)) {
                $errors[] = "Baris {$rowNumber}: Duplikat NIP '{$employeeCode}' dalam berkas.";
                $skipped++;

                continue;
            }
            $fileCodes[] = $employeeCode;

            // Check duplicate in DB (employees table or users table)
            if (Employee::where('employee_code', $employeeCode)->exists() || User::where('username', $employeeCode)->exists()) {
                $errors[] = "Baris {$rowNumber}: Pegawai dengan kode/username '{$employeeCode}' sudah terdaftar di database.";
                $skipped++;

                continue;
            }
        }

        // If in strict mode and any error/duplicate found, abort completely (rollback)
        if ($strict && ! empty($errors)) {
            return [
                'success' => false,
                'total' => $total,
                'imported' => 0,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        }

        // Pass 2: Insertion in database transaction
        $insertedCodes = [];
        try {
            DB::transaction(function () use ($rows, $strict, &$imported, &$skipped, &$errors, &$insertedCodes) {
                foreach ($rows as $index => $cleanRow) {
                    $rowNumber = $index + 2;

                    $employeeCode = $cleanRow['employee_code'] ?? $cleanRow['nip'] ?? $cleanRow['kode_pegawai'] ?? null;
                    $fullName = $cleanRow['full_name'] ?? $cleanRow['nama'] ?? $cleanRow['nama_lengkap'] ?? null;

                    if (empty($employeeCode) || empty($fullName)) {
                        continue;
                    }

                    // In non-strict mode, skip if already exists in DB
                    if (! $strict && (Employee::where('employee_code', $employeeCode)->exists() || in_array($employeeCode, $insertedCodes, true))) {
                        continue;
                    }

                    // Resolve position
                    $positionInput = $cleanRow['position_code'] ?? $cleanRow['kode_posisi'] ?? $cleanRow['posisi'] ?? 'OB';
                    $position = Position::where('code', strtoupper($positionInput))
                        ->orWhere('name', 'like', "%{$positionInput}%")
                        ->first();

                    if (! $position) {
                        $position = Position::first();
                    }

                    // Resolve supervisor
                    $supervisorInput = $cleanRow['supervisor_username'] ?? $cleanRow['supervisor'] ?? null;
                    $supervisor = null;
                    if ($supervisorInput) {
                        $supervisor = User::role('supervisor')
                            ->where(function ($q) use ($supervisorInput) {
                                $q->where('username', $supervisorInput)
                                    ->orWhere('name', 'like', "%{$supervisorInput}%");
                            })->first();
                    }

                    // Resolve atasan
                    $atasanInput = $cleanRow['atasan_username'] ?? $cleanRow['atasan'] ?? null;
                    $atasan = null;
                    if ($atasanInput) {
                        $atasan = User::role('atasan')
                            ->where(function ($q) use ($atasanInput) {
                                $q->where('username', $atasanInput)
                                    ->orWhere('name', 'like', "%{$atasanInput}%");
                            })->first();
                    }

                    $gender = strtoupper($cleanRow['gender'] ?? $cleanRow['jenis_kelamin'] ?? 'L');
                    if (! in_array($gender, ['L', 'P'], true)) {
                        $gender = 'L';
                    }

                    $baseSalary = (float) ($cleanRow['base_salary'] ?? $cleanRow['gaji_pokok'] ?? $cleanRow['honor'] ?? 3200000);
                    $leaveQuota = (int) ($cleanRow['leave_quota_balance'] ?? $cleanRow['saldo_cuti'] ?? 12);
                    $spkNumber = $cleanRow['spk_number'] ?? $cleanRow['nomor_spk'] ?? 'SPK-20/WPB.08/PPK/2025';

                    $startDate = ! empty($cleanRow['contract_start_date']) ? $cleanRow['contract_start_date'] : null;
                    $endDate = ! empty($cleanRow['contract_end_date']) ? $cleanRow['contract_end_date'] : null;

                    // Create user
                    $user = User::firstOrCreate(
                        ['username' => $employeeCode],
                        [
                            'name' => $fullName,
                            'email' => Str::lower($employeeCode).'@djpb.local',
                            'password' => Hash::make('password'),
                            'is_active' => true,
                            'must_change_password' => true,
                        ]
                    );
                    $user->syncRoles(['karyawan']);

                    // Create employee
                    Employee::create([
                        'user_id' => $user->id,
                        'employee_code' => $employeeCode,
                        'full_name' => $fullName,
                        'gender' => $gender,
                        'phone_number' => $cleanRow['phone_number'] ?? $cleanRow['no_hp'] ?? null,
                        'position_id' => $position->id,
                        'supervisor_id' => $supervisor?->id,
                        'atasan_id' => $atasan?->id,
                        'base_salary' => $baseSalary,
                        'leave_quota_balance' => $leaveQuota,
                        'spk_number' => $spkNumber,
                        'contract_start_date' => $startDate,
                        'contract_end_date' => $endDate,
                        'is_active' => true,
                    ]);

                    $insertedCodes[] = $employeeCode;
                    $imported++;
                }
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'total' => $total,
                'imported' => 0,
                'skipped' => $total,
                'errors' => ["Gagal menyimpan data pegawai ke database: {$e->getMessage()}"],
            ];
        }

        // Record Audit Log if any imported
        if ($imported > 0) {
            AuditLog::create([
                'user_id' => $importedByUserId,
                'action' => 'EMPLOYEES_IMPORTED',
                'entity_type' => Employee::class,
                'new_values' => [
                    'total_rows' => $total,
                    'imported_count' => $imported,
                    'skipped_count' => $skipped,
                    'strict_mode' => $strict,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return [
            'success' => $imported > 0,
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Backward-compatible alias for CSV imports.
     *
     * @return array{
     *     success: bool,
     *     total: int,
     *     imported: int,
     *     skipped: int,
     *     errors: list<string>
     * }
     */
    public function importFromCsv(string $filePath, ?int $importedByUserId = null, bool $strict = true): array
    {
        return $this->importFile($filePath, $importedByUserId, $strict);
    }

    /**
     * Extract normalized rows from CSV or XLSX file.
     *
     * @return list<array<string, string>>
     */
    public function extractRowsFromFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return $this->extractRowsFromXlsx($filePath);
        }

        return $this->extractRowsFromCsv($filePath);
    }

    /**
     * Extract rows from CSV file using League\Csv.
     *
     * @return list<array<string, string>>
     */
    protected function extractRowsFromCsv(string $filePath): array
    {
        $csv = CsvReader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $row) {
            $cleanRow = [];
            foreach ($row as $key => $value) {
                $cleanRow[strtolower(trim((string) $key))] = trim((string) $value);
            }
            $rows[] = $cleanRow;
        }

        return $rows;
    }

    /**
     * Extract rows from XLSX file using OpenSpout.
     *
     * @return list<array<string, string>>
     */
    protected function extractRowsFromXlsx(string $filePath): array
    {
        $reader = new XlsxReader;
        $reader->open($filePath);

        $headers = [];
        $rows = [];
        $isFirstRow = true;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $cellValues = array_map(fn ($cell) => trim((string) $cell->getValue()), $cells);

                if ($isFirstRow) {
                    $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $cellValues);
                    $isFirstRow = false;

                    continue;
                }

                // If entire row is empty, skip
                if (empty(array_filter($cellValues))) {
                    continue;
                }

                $cleanRow = [];
                foreach ($headers as $colIdx => $headerName) {
                    if (! empty($headerName)) {
                        $cleanRow[$headerName] = $cellValues[$colIdx] ?? '';
                    }
                }
                $rows[] = $cleanRow;
            }

            break; // Read only the first sheet
        }

        $reader->close();

        return $rows;
    }

    /**
     * Generate standard CSV template string for download.
     */
    public function generateCsvTemplate(): string
    {
        $headers = [
            'employee_code',
            'full_name',
            'gender',
            'phone_number',
            'position_code',
            'supervisor_username',
            'atasan_username',
            'base_salary',
            'leave_quota_balance',
            'spk_number',
            'contract_start_date',
            'contract_end_date',
        ];

        $sampleRows = [
            [
                'PPNPN-2025-002',
                'Budi Santoso',
                'L',
                '081234567891',
                'DRIVER',
                'supervisor',
                'atasan',
                '3200000',
                '12',
                'SPK-20/WPB.08/PPK/2025',
                '2026-01-01',
                '2027-12-31',
            ],
            [
                'PPNPN-2025-003',
                'Siti Rahmawati',
                'P',
                '081234567892',
                'RECEPTIONIST',
                'supervisor',
                'atasan',
                '3200000',
                '12',
                'SPK-20/WPB.08/PPK/2025',
                '2026-01-01',
                '2027-12-31',
            ],
            [
                'PPNPN-2025-004',
                'Joko Purwanto',
                'L',
                '081234567893',
                'SECURITY_DAY',
                'supervisor',
                'atasan',
                '3200000',
                '12',
                'SPK-20/WPB.08/PPK/2025',
                '2026-01-01',
                '2027-12-31',
            ],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($sampleRows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent ?: '';
    }
}
