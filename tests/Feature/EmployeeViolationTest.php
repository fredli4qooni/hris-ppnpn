<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeViolations\EmployeeViolationResource;
use App\Models\Employee;
use App\Models\EmployeeViolation;
use App\Models\Position;
use App\Models\User;
use App\Models\ViolationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeViolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $otherSupervisorUser;

    protected User $karyawanUser;

    protected Employee $subordinateEmployee;

    protected Employee $otherEmployee;

    protected Position $driverPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');

        $this->adminUser = User::where('username', 'admin')->first();
        $this->supervisorUser = User::where('username', 'supervisor')->first();
        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->subordinateEmployee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->driverPosition = Position::where('code', 'DRIVER')->first();

        // Ensure subordinate employee is linked to supervisorUser
        $this->subordinateEmployee->update(['supervisor_id' => $this->supervisorUser->id]);

        // Create second supervisor and second employee for scoping tests
        $this->otherSupervisorUser = User::create([
            'username' => 'supervisor2',
            'name' => 'Supervisor Dua',
            'email' => 'supervisor2@djpb.kemenkeu.go.id',
            'password' => bcrypt('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->otherSupervisorUser->syncRoles(['supervisor']);

        $otherUser = User::create([
            'username' => 'ppnpn02',
            'name' => 'Karyawan Dua',
            'email' => 'ppnpn02@djpb.kemenkeu.go.id',
            'password' => bcrypt('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $otherUser->syncRoles(['karyawan']);

        $this->otherEmployee = Employee::create([
            'user_id' => $otherUser->id,
            'employee_code' => 'PPNPN-2025-002',
            'full_name' => 'Karyawan Dua',
            'gender' => 'L',
            'phone_number' => '081234567892',
            'position_id' => $this->driverPosition->id,
            'supervisor_id' => $this->otherSupervisorUser->id,
            'base_salary' => 3000000,
            'leave_quota_balance' => 12,
            'is_active' => true,
        ]);
    }

    /**
     * Test ViolationCatalogSeeder accurately seeds all 45 clauses from Lampiran II SPK.
     */
    public function test_violation_catalog_seeder_seeds_all_forty_five_clauses(): void
    {
        $this->assertEquals(45, ViolationCatalog::count());

        $this->assertEquals(14, ViolationCatalog::where('category', ViolationCatalog::CATEGORY_KEHADIRAN_A)->count());
        $this->assertEquals(15, ViolationCatalog::where('category', ViolationCatalog::CATEGORY_KEWAJIBAN_B)->count());
        $this->assertEquals(16, ViolationCatalog::where('category', ViolationCatalog::CATEGORY_KEDISIPLINAN_C)->count());

        // Check specific clauses
        $clauseA1 = ViolationCatalog::where('clause_number', 'A1')->first();
        $this->assertNotNull($clauseA1);
        $this->assertEquals(0, $clauseA1->points);

        $clauseA12 = ViolationCatalog::where('clause_number', 'A12')->first();
        $this->assertNotNull($clauseA12);
        $this->assertEquals(20, $clauseA12->points);

        $clauseB1 = ViolationCatalog::where('clause_number', 'B1')->first();
        $this->assertNotNull($clauseB1);
        $this->assertEquals(100, $clauseB1->points);

        $clauseC1 = ViolationCatalog::where('clause_number', 'C1')->first();
        $this->assertNotNull($clauseC1);
        $this->assertEquals(100, $clauseC1->points);
    }

    /**
     * Test supervisor can access the employee violations Filament page.
     */
    public function test_supervisor_can_access_violation_resource_index(): void
    {
        $response = $this->actingAs($this->supervisorUser)
            ->get(EmployeeViolationResource::getUrl('index'));

        $response->assertOk();
    }

    /**
     * Test karyawan role is forbidden from accessing employee violations Filament page.
     */
    public function test_karyawan_cannot_access_violation_resource(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get(EmployeeViolationResource::getUrl('index'));

        $response->assertForbidden();
    }

    /**
     * Test supervisor can record an employee violation with evidence upload.
     */
    public function test_supervisor_can_record_employee_violation(): void
    {
        $clauseB8 = ViolationCatalog::where('clause_number', 'B8')->first(); // 20 Poin

        $fakePhoto = UploadedFile::fake()->image('bukti_pelanggaran.jpg', 600, 400);
        $storedPath = $fakePhoto->store('violations/evidence', 'public');

        $violation = EmployeeViolation::create([
            'employee_id' => $this->subordinateEmployee->id,
            'violation_catalog_id' => $clauseB8->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Tidak melaksanakan pekerjaan secara cermat dan bertanggung jawab pada pemeliharaan inventaris.',
            'evidence_photo_path' => $storedPath,
            'points' => $clauseB8->points,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('employee_violations', [
            'id' => $violation->id,
            'employee_id' => $this->subordinateEmployee->id,
            'points' => 20,
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($violation->isActive());
        $this->assertFalse($violation->isCancelled());
        $this->assertEquals('B8', $violation->catalog->clause_number);
        $this->assertEquals($this->supervisorUser->id, $violation->supervisor->id);
    }

    /**
     * Test supervisor or admin can officially cancel a violation with reason.
     */
    public function test_supervisor_can_cancel_violation_with_audit_reason(): void
    {
        $clauseA5 = ViolationCatalog::where('clause_number', 'A5')->first(); // 25 Poin

        $violation = EmployeeViolation::create([
            'employee_id' => $this->subordinateEmployee->id,
            'violation_catalog_id' => $clauseA5->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Meninggalkan area kerja.',
            'points' => $clauseA5->points,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertTrue($violation->isActive());

        // Cancel violation
        $violation->cancel('Klarifikasi diterima: Pegawai mendapat disposisi mendadak dari Kasubbag Umum.', $this->supervisorUser->id);

        $violation->refresh();
        $this->assertFalse($violation->isActive());
        $this->assertTrue($violation->isCancelled());
        $this->assertEquals(EmployeeViolation::STATUS_CANCELLED, $violation->status);
        $this->assertEquals('Klarifikasi diterima: Pegawai mendapat disposisi mendadak dari Kasubbag Umum.', $violation->cancellation_reason);
        $this->assertEquals($this->supervisorUser->id, $violation->cancelled_by_id);
    }

    /**
     * Test supervisor query isolation: only sees subordinate violations.
     */
    public function test_supervisor_only_sees_subordinate_violations(): void
    {
        $clauseA2 = ViolationCatalog::where('clause_number', 'A2')->first();

        // Violation for Supervisor 1's subordinate
        EmployeeViolation::create([
            'employee_id' => $this->subordinateEmployee->id,
            'violation_catalog_id' => $clauseA2->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Terlambat 30 menit.',
            'points' => 5,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        // Violation for Supervisor 2's subordinate
        EmployeeViolation::create([
            'employee_id' => $this->otherEmployee->id,
            'violation_catalog_id' => $clauseA2->id,
            'supervisor_id' => $this->otherSupervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Terlambat 40 menit.',
            'points' => 5,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->supervisorUser);
        $scopedQuery = EmployeeViolationResource::getEloquentQuery();

        $this->assertEquals(1, $scopedQuery->count());
        $this->assertEquals($this->subordinateEmployee->id, $scopedQuery->first()->employee_id);
    }

    /**
     * Test employee model has violations relationship.
     */
    public function test_employee_violations_relationship(): void
    {
        $clauseA6 = ViolationCatalog::where('clause_number', 'A6')->first();

        EmployeeViolation::create([
            'employee_id' => $this->subordinateEmployee->id,
            'violation_catalog_id' => $clauseA6->id,
            'supervisor_id' => $this->supervisorUser->id,
            'incident_date' => '2026-11-18',
            'notes' => 'Terlambat kembali setelah istirahat.',
            'points' => 3,
            'status' => EmployeeViolation::STATUS_ACTIVE,
        ]);

        $this->assertEquals(1, $this->subordinateEmployee->violations()->count());
        $this->assertEquals(3, $this->subordinateEmployee->violations()->first()->points);
    }
}
