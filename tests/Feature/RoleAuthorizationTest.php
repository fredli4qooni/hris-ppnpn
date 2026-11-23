<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Unauthenticated user is redirected to the Filament login page.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Admin role can access the Filament backoffice panel.
     */
    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::where('username', 'admin')->first();

        $panel = Filament::getPanel('admin');
        $this->assertTrue($admin->canAccessPanel($panel));

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSuccessful();
    }

    /**
     * Supervisor role can access the Filament backoffice panel.
     */
    public function test_supervisor_can_access_filament_panel(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();

        $panel = Filament::getPanel('admin');
        $this->assertTrue($supervisor->canAccessPanel($panel));

        $response = $this->actingAs($supervisor)->get('/admin');
        $response->assertSuccessful();
    }

    /**
     * Atasan role can access the Filament backoffice panel.
     */
    public function test_atasan_can_access_filament_panel(): void
    {
        $atasan = User::where('username', 'atasan')->first();

        $panel = Filament::getPanel('admin');
        $this->assertTrue($atasan->canAccessPanel($panel));

        $response = $this->actingAs($atasan)->get('/admin');
        $response->assertSuccessful();
    }

    /**
     * Karyawan (PPNPN) CANNOT access the Filament backoffice panel (forbidden).
     */
    public function test_karyawan_cannot_access_filament_panel(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        $panel = Filament::getPanel('admin');
        $this->assertFalse($karyawan->canAccessPanel($panel));

        $response = $this->actingAs($karyawan)->get('/admin');
        $response->assertForbidden();
    }

    /**
     * Inactive user is barred from accessing the panel even with admin role.
     */
    public function test_inactive_user_cannot_access_panel(): void
    {
        $admin = User::where('username', 'admin')->first();
        $admin->update(['is_active' => false]);

        $panel = Filament::getPanel('admin');
        $this->assertFalse($admin->canAccessPanel($panel));

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertForbidden();
    }

    /**
     * Test role-based granular permissions mapping.
     */
    public function test_role_granular_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $atasan = User::where('username', 'atasan')->first();
        $karyawan = User::where('username', 'ppnpn01')->first();

        // Admin checks
        $this->assertTrue($admin->can('manage-employees'));
        $this->assertTrue($admin->can('close-periods'));
        $this->assertTrue($admin->can('export-reports'));

        // Supervisor checks
        $this->assertTrue($supervisor->can('record-violations'));
        $this->assertTrue($supervisor->can('approve-leaves-level-1'));
        $this->assertFalse($supervisor->can('manage-employees'));

        // Atasan checks
        $this->assertTrue($atasan->can('approve-leaves-level-2'));
        $this->assertTrue($atasan->can('view-sanctions'));
        $this->assertFalse($atasan->can('record-violations'));

        // Karyawan checks
        $this->assertTrue($karyawan->can('record-attendance'));
        $this->assertTrue($karyawan->can('submit-leave-requests'));
        $this->assertFalse($karyawan->can('record-violations'));
        $this->assertFalse($karyawan->can('manage-employees'));
    }
}
