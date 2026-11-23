<?php

namespace Tests\Feature;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test admin can access employee list page.
     */
    public function test_admin_can_render_employee_list(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/employees');

        $response->assertSuccessful();
        $response->assertSee('Ahmad Subari');
    }

    /**
     * Test supervisor can view employee list.
     */
    public function test_supervisor_can_render_employee_list(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();

        $response = $this->actingAs($supervisor)->get('/admin/employees');

        $response->assertSuccessful();
    }

    /**
     * Test karyawan is forbidden from accessing employee list.
     */
    public function test_karyawan_cannot_access_employee_list(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        $response = $this->actingAs($karyawan)->get('/admin/employees');

        $response->assertForbidden();
    }

    /**
     * Test admin can create an employee and user account is provisioned automatically.
     */
    public function test_admin_can_create_employee_with_auto_provisioned_user(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $atasan = User::where('username', 'atasan')->first();
        $driverPosition = Position::where('code', 'DRIVER')->first();

        Livewire::actingAs($admin)
            ->test(CreateEmployee::class)
            ->fillForm([
                'employee_code' => 'PPNPN-2025-002',
                'full_name' => 'Budi Santoso',
                'gender' => 'L',
                'phone_number' => '082112345678',
                'position_id' => $driverPosition->id,
                'supervisor_id' => $supervisor->id,
                'atasan_id' => $atasan->id,
                'spk_number' => 'SPK-20/WPB.08/PPK/2025',
                'base_salary' => 3300000,
                'leave_quota_balance' => 12,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify Employee created
        $employee = Employee::where('employee_code', 'PPNPN-2025-002')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Budi Santoso', $employee->full_name);
        $this->assertEquals($driverPosition->id, $employee->position_id);
        $this->assertEquals($supervisor->id, $employee->supervisor_id);

        // Verify User account created automatically
        $user = User::where('username', 'PPNPN-2025-002')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertTrue($user->hasRole('karyawan'));
        $this->assertTrue($user->must_change_password);
        $this->assertEquals($user->id, $employee->user_id);

        // Verify Audit Log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EMPLOYEE_CREATED',
            'entity_type' => Employee::class,
            'entity_id' => $employee->id,
        ]);
    }

    /**
     * Test editing an employee synchronizes user record and logs audit.
     */
    public function test_editing_employee_synchronizes_user_and_logs_audit(): void
    {
        $admin = User::where('username', 'admin')->first();
        $employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();

        Livewire::actingAs($admin)
            ->test(EditEmployee::class, ['record' => $employee->id])
            ->fillForm([
                'full_name' => 'Ahmad Subari Updated',
                'base_salary' => 3500000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $employee->refresh();
        $this->assertEquals('Ahmad Subari Updated', $employee->full_name);
        $this->assertEquals(3500000, (float) $employee->base_salary);

        // Verify associated User is updated
        $user = $employee->user;
        $this->assertEquals('Ahmad Subari Updated', $user->name);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EMPLOYEE_UPDATED',
            'entity_type' => Employee::class,
            'entity_id' => $employee->id,
        ]);
    }

    /**
     * Test soft-deleting an employee.
     */
    public function test_admin_can_soft_delete_employee(): void
    {
        $admin = User::where('username', 'admin')->first();
        $employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();

        Livewire::actingAs($admin)
            ->test(EditEmployee::class, ['record' => $employee->id])
            ->callAction('delete');

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }
}
