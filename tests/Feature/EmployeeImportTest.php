<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->atasanUser = User::where('username', 'atasan')->first();
        $this->position = Position::first();
    }

    public function test_service_generates_valid_csv_template(): void
    {
        $service = new EmployeeImportService;
        $template = $service->generateCsvTemplate();

        $this->assertNotEmpty($template);
        $this->assertStringContainsString('employee_code', $template);
        $this->assertStringContainsString('full_name', $template);
        $this->assertStringContainsString('position_code', $template);
    }

    public function test_can_import_valid_employees_from_csv(): void
    {
        Storage::fake('local');

        $csvContent = "employee_code,full_name,gender,phone_number,position_code,supervisor_username,atasan_username,base_salary,leave_quota_balance,spk_number,contract_start_date,contract_end_date\n"
            ."PPNPN-TEST-001,Ahmad Fauzi,L,081234567891,OB,supervisor,atasan,3200000,12,SPK-01/2026,2026-01-01,2026-12-31\n"
            ."PPNPN-TEST-002,Dewi Lestari,P,081234567892,DRIVER,supervisor,atasan,3200000,12,SPK-01/2026,2026-01-01,2026-12-31\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempPath, $csvContent);

        $service = new EmployeeImportService;
        $result = $service->importFromCsv($tempPath, $this->adminUser->id, true);

        unlink($tempPath);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        // Verify employees created
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'PPNPN-TEST-001',
            'full_name' => 'Ahmad Fauzi',
            'supervisor_id' => $this->supervisorUser->id,
            'atasan_id' => $this->atasanUser->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'PPNPN-TEST-002',
            'full_name' => 'Dewi Lestari',
            'gender' => 'P',
        ]);

        // Verify users auto-created
        $user1 = User::where('username', 'PPNPN-TEST-001')->first();
        $this->assertNotNull($user1);
        $this->assertTrue($user1->must_change_password);
        $this->assertTrue($user1->hasRole('karyawan'));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'EMPLOYEES_IMPORTED',
            'entity_type' => Employee::class,
        ]);
    }

    public function test_rollback_when_duplicate_nip_exists_in_strict_mode(): void
    {
        // PPNPN-2025-001 already exists from SuperAdminUserSeeder
        $csvContent = "employee_code,full_name,gender,position_code\n"
            ."PPNPN-NEW-999,Pegawai Baru,L,OB\n"
            ."PPNPN-2025-001,Ahmad Subari Duplikat,L,OB\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempPath, $csvContent);

        $service = new EmployeeImportService;
        $result = $service->importFromCsv($tempPath, $this->adminUser->id, true);

        unlink($tempPath);

        // Assert failure and rollback
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('PPNPN-2025-001', $result['errors'][0]);

        // Assert that new employee was NOT created due to rollback
        $this->assertDatabaseMissing('employees', [
            'employee_code' => 'PPNPN-NEW-999',
        ]);
    }

    public function test_rollback_when_duplicate_nip_within_same_file(): void
    {
        // CSV with duplicate in the file
        $csvContent = "employee_code,full_name,gender,position_code\n"
            ."PPNPN-DUP-001,Pegawai A,L,OB\n"
            ."PPNPN-DUP-001,Pegawai B,L,OB\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempPath, $csvContent);

        $service = new EmployeeImportService;
        $result = $service->importFromCsv($tempPath, $this->adminUser->id, true);

        unlink($tempPath);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Duplikat NIP', $result['errors'][0]);

        // Neither should exist in database
        $this->assertDatabaseMissing('employees', [
            'employee_code' => 'PPNPN-DUP-001',
        ]);
    }

    public function test_non_strict_mode_skips_duplicates_and_imports_valid_rows(): void
    {
        // PPNPN-2025-001 exists from seed
        $csvContent = "employee_code,full_name,gender,position_code\n"
            ."PPNPN-VALID-777,Pegawai Baru Valid,L,OB\n"
            ."PPNPN-2025-001,Ahmad Subari Lewat,L,OB\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempPath, $csvContent);

        $service = new EmployeeImportService;
        $result = $service->importFromCsv($tempPath, $this->adminUser->id, false);

        unlink($tempPath);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'PPNPN-VALID-777',
        ]);
    }

    public function test_admin_can_access_filament_employee_resource(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/employees');

        $response->assertSuccessful();
    }
}
