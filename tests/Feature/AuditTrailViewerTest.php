<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditTrailViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected User $karyawanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->adminUser->update(['must_change_password' => false]);

        $this->supervisorUser = User::where('username', 'supervisor')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'supervisor'))->firstOrFail();
        $this->supervisorUser->update(['must_change_password' => false]);

        $this->atasanUser = User::where('username', 'atasan')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'atasan'))->firstOrFail();
        $this->atasanUser->update(['must_change_password' => false]);

        $this->karyawanUser = User::where('username', 'ppnpn01')->first() ?? User::whereHas('roles', fn ($q) => $q->where('name', 'karyawan'))->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);
    }

    /**
     * Test 1: Only admin can access the audit log index page.
     */
    public function test_admin_can_access_audit_log_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/audit-logs');

        $response->assertSuccessful();
    }

    /**
     * Test 2: Supervisor, Atasan, and Karyawan cannot access audit logs (FR-902).
     */
    public function test_non_admin_users_cannot_access_audit_logs(): void
    {
        // Permission check
        $this->actingAs($this->supervisorUser);
        $this->assertFalse(AuditLogResource::canAccess());
        $this->assertFalse(AuditLogResource::canViewAny());

        $this->actingAs($this->atasanUser);
        $this->assertFalse(AuditLogResource::canAccess());
        $this->assertFalse(AuditLogResource::canViewAny());

        $this->actingAs($this->karyawanUser);
        $this->assertFalse(AuditLogResource::canAccess());
        $this->assertFalse(AuditLogResource::canViewAny());

        // HTTP access: Supervisor & Atasan are barred from the resource
        $responseSupervisor = $this->actingAs($this->supervisorUser)
            ->get('/admin/audit-logs');
        $this->assertTrue($responseSupervisor->isForbidden() || $responseSupervisor->isRedirect());

        $responseAtasan = $this->actingAs($this->atasanUser)
            ->get('/admin/audit-logs');
        $this->assertTrue($responseAtasan->isForbidden() || $responseAtasan->isRedirect());

        // Karyawan has no panel access
        $this->actingAs($this->karyawanUser)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }

    /**
     * Test 3: Immutability enforcement at resource and Eloquent model levels (FR-902).
     */
    public function test_audit_logs_are_strictly_immutable(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'LOGIN',
            'entity_type' => User::class,
            'entity_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'created_at' => now(),
        ]);

        // Resource permissions
        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertFalse(AuditLogResource::canEdit($log));
        $this->assertFalse(AuditLogResource::canDelete($log));
        $this->assertFalse(AuditLogResource::canDeleteAny());

        // Model level immutability (updating prohibited)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rekaman audit log bersifat permanen dan tidak dapat diubah');
        $log->update(['action' => 'MODIFIED_ACTION']);
    }

    /**
     * Test 4: Model level immutability prohibits deletion (FR-902).
     */
    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'LOGIN',
            'entity_type' => User::class,
            'entity_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.10',
            'created_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rekaman audit log bersifat permanen dan tidak dapat dihapus');
        $log->delete();
    }

    /**
     * Test 5: Audit log table renders records and shows actor, action, IP, and entity details.
     */
    public function test_audit_log_table_renders_and_lists_records(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'CONTRACT_EXTENDED',
            'entity_type' => Employee::class,
            'entity_id' => 99,
            'old_values' => ['contract_end_date' => '2026-11-30'],
            'new_values' => ['contract_end_date' => '2027-11-30'],
            'ip_address' => '10.15.22.4',
            'user_agent' => 'PostmanRuntime/7.32.0',
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => null, // Scheduler
            'action' => 'CONTRACT_AUTO_INACTIVATED',
            'entity_type' => Employee::class,
            'entity_id' => 102,
            'old_values' => ['status' => 'ACTIVE'],
            'new_values' => ['status' => 'INACTIVE'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Artisan Scheduler',
            'created_at' => now()->subHours(2),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ListAuditLogs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(AuditLog::all())
            ->assertSee('CONTRACT_EXTENDED')
            ->assertSee('CONTRACT_AUTO_INACTIVATED')
            ->assertSee($this->adminUser->name)
            ->assertSee('Sistem Otomatis (Scheduler)')
            ->assertSee('10.15.22.4');
    }

    /**
     * Test 6: Audit log table filters by action, user, and date range.
     */
    public function test_audit_log_table_filters(): void
    {
        $log1 = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'CONTRACT_EXTENDED',
            'entity_type' => Employee::class,
            'entity_id' => 1,
            'created_at' => Carbon::parse('2026-11-01 10:00:00'),
        ]);

        $log2 = AuditLog::create([
            'user_id' => $this->supervisorUser->id,
            'action' => 'VIOLATION_DRAFT_CONFIRMED',
            'entity_type' => Employee::class,
            'entity_id' => 2,
            'created_at' => Carbon::parse('2026-11-15 14:00:00'),
        ]);

        // Filter by action
        Livewire::actingAs($this->adminUser)
            ->test(ListAuditLogs::class)
            ->filterTable('action', 'CONTRACT_EXTENDED')
            ->assertCanSeeTableRecords([$log1])
            ->assertCanNotSeeTableRecords([$log2]);

        // Filter by user
        Livewire::actingAs($this->adminUser)
            ->test(ListAuditLogs::class)
            ->filterTable('user_id', $this->supervisorUser->id)
            ->assertCanSeeTableRecords([$log2])
            ->assertCanNotSeeTableRecords([$log1]);

        // Filter by date range
        Livewire::actingAs($this->adminUser)
            ->test(ListAuditLogs::class)
            ->filterTable('created_at', [
                'from' => '2026-11-10',
                'until' => '2026-11-20',
            ])
            ->assertCanSeeTableRecords([$log2])
            ->assertCanNotSeeTableRecords([$log1]);
    }

    /**
     * Test 7: Diff viewer modal view renders before and after JSON data with modified attributes.
     */
    public function test_diff_viewer_modal_displays_before_and_after_data(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'CONTRACT_EXTENDED',
            'entity_type' => Employee::class,
            'entity_id' => 50,
            'old_values' => [
                'contract_end_date' => '2026-11-30',
                'salary' => 3500000,
                'position' => 'Driver',
            ],
            'new_values' => [
                'contract_end_date' => '2027-11-30',
                'salary' => 3800000,
                'position' => 'Driver',
            ],
            'ip_address' => '172.16.0.5',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0',
            'created_at' => now(),
        ]);

        $rendered = $this->actingAs($this->adminUser)
            ->view('filament.resources.audit-logs.modals.diff-viewer', ['record' => $log]);

        $rendered->assertSee('CONTRACT_EXTENDED');
        $rendered->assertSee($this->adminUser->name);
        $rendered->assertSee('172.16.0.5');
        $rendered->assertSee('Nilai Sebelum (Old Values)');
        $rendered->assertSee('Nilai Sesudah (New Values)');
        $rendered->assertSee('2026-11-30');
        $rendered->assertSee('2027-11-30');
        $rendered->assertSee('contract_end_date');
        $rendered->assertSee('salary');
    }
}
