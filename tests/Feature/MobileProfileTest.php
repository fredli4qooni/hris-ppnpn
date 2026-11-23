<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\BiometricVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        // Set password changed so they can access the mobile portal
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
    }

    /**
     * Test unauthenticated guests are redirected to login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_profile(): void
    {
        $response = $this->get('/mobile/profile');

        $response->assertRedirect('/login');
    }

    /**
     * Test user with must_change_password = true cannot access profile before changing password.
     */
    public function test_user_with_must_change_password_is_locked_to_first_change(): void
    {
        $this->karyawanUser->update(['must_change_password' => true]);

        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/profile');

        $response->assertRedirect('/password/first-change');
    }

    /**
     * Test authenticated employee can render and view their complete profile (FR-102 / US-206).
     */
    public function test_authenticated_karyawan_can_view_complete_profile(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/profile');

        $response->assertSuccessful();

        // FR-102 Assertions:
        $response->assertSee('Ahmad Subari');
        $response->assertSee('PPNPN-2025-001');
        $response->assertSee($this->employee->position->name);
        $response->assertSee('SPK-20/WPB.08/PPK/2025');
        $response->assertSee('12'); // Leave balance
        $response->assertSee('Supervisor Lapangan');
        $response->assertSee('Kepala Bagian Umum');
        $response->assertSee('3.200.000'); // Base salary
    }

    /**
     * Test profile displays registered biometric status when face template is enrolled.
     */
    public function test_profile_displays_biometric_status_badges(): void
    {
        // 1. Without face template
        $responseBefore = $this->actingAs($this->karyawanUser)
            ->get('/mobile/profile');

        $responseBefore->assertSuccessful();
        $responseBefore->assertSee('Belum Terdaftar');

        // 2. Enroll face template
        $service = new BiometricVerificationService;
        $vector = $service->generateMockDescriptor(0.5);
        $service->enrollEmployee($this->employee->id, $vector);

        $responseAfter = $this->actingAs($this->karyawanUser)
            ->get('/mobile/profile');

        $responseAfter->assertSuccessful();
        $responseAfter->assertSee('Terdaftar');
        $responseAfter->assertSee('AES-256');
    }

    /**
     * Test mobile home renders successfully and contains profile link.
     */
    public function test_mobile_home_renders_and_contains_profile_link(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/home');

        $response->assertSuccessful();
        $response->assertSee('Ahmad Subari');
        $response->assertSee(route('mobile.profile'));
    }
}
