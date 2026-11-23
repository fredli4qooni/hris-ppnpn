<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaAndModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test that all default positions are properly seeded.
     */
    public function test_default_positions_are_seeded(): void
    {
        $this->assertDatabaseCount('positions', 6);

        $ob = Position::where('code', 'OB')->first();
        $this->assertNotNull($ob);
        $this->assertEquals('06:30:00', $ob->default_start_time);
        $this->assertFalse($ob->is_overnight);

        $nightSecurity = Position::where('code', 'SECURITY_NIGHT')->first();
        $this->assertNotNull($nightSecurity);
        $this->assertEquals('19:00:00', $nightSecurity->default_start_time);
        $this->assertTrue($nightSecurity->is_overnight);
    }

    /**
     * Test default office location geofence parameters.
     */
    public function test_default_office_location_is_seeded(): void
    {
        $office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();

        $this->assertNotNull($office);
        $this->assertEquals(-5.3971400, (float) $office->latitude);
        $this->assertEquals(105.2667900, (float) $office->longitude);
        $this->assertEquals(100, $office->radius_meters);
        $this->assertTrue($office->is_active);
    }

    /**
     * Test default user roles and access capabilities.
     */
    public function test_default_users_and_roles_are_seeded(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));

        $supervisor = User::where('username', 'supervisor')->first();
        $this->assertNotNull($supervisor);
        $this->assertTrue($supervisor->hasRole('supervisor'));

        $atasan = User::where('username', 'atasan')->first();
        $this->assertNotNull($atasan);
        $this->assertTrue($atasan->hasRole('atasan'));

        $karyawan = User::where('username', 'ppnpn01')->first();
        $this->assertNotNull($karyawan);
        $this->assertTrue($karyawan->hasRole('karyawan'));
        $this->assertTrue($karyawan->must_change_password);
    }

    /**
     * Test employee relations to user, position, supervisor, and atasan.
     */
    public function test_employee_relationships(): void
    {
        $employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Ahmad Subari', $employee->full_name);
        $this->assertEquals('OB', $employee->position->code);
        $this->assertEquals('supervisor', $employee->supervisor->username);
        $this->assertEquals('atasan', $employee->atasan->username);
        $this->assertEquals(12, $employee->leave_quota_balance);
        $this->assertEquals(3200000.00, (float) $employee->base_salary);
    }

    /**
     * Test audit log model creation and JSON casts.
     */
    public function test_audit_log_creation(): void
    {
        $admin = User::where('username', 'admin')->first();

        $log = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'TEST_ACTION',
            'entity_type' => Position::class,
            'entity_id' => 1,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'TEST_ACTION',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals(['name' => 'Old Name'], $log->old_values);
        $this->assertEquals(['name' => 'New Name'], $log->new_values);
    }
}
