<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemUserGuide;
use App\Models\User;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGuideAndDocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected User $adminUser;

    protected User $supervisorUser;

    protected User $atasanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->supervisorUser = User::where('username', 'supervisor')->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->firstOrFail();
    }

    /**
     * Test unauthenticated guest cannot access mobile guide and gets redirected to login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('mobile.guide'));
        $response->assertRedirect(route('login'));

        $pdfResponse = $this->get(route('mobile.guide.download-pdf'));
        $pdfResponse->assertRedirect(route('login'));
    }

    /**
     * Test authenticated employee can access mobile interactive user guide with all 5 operational tabs.
     */
    public function test_authenticated_employee_can_access_mobile_guide_with_all_tabs(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.guide'));

        $response->assertStatus(200);
        $response->assertSee('Panduan Operasional Presensi & Disiplin', false);
        $response->assertSee('1. Biometrik', false);
        $response->assertSee('2. Jam & TL', false);
        $response->assertSee('3. Fallback', false);
        $response->assertSee('4. Cuti/Kahar', false);
        $response->assertSee('5. Sanksi SP', false);
        $response->assertSee('Pertanyaan yang Sering Diajukan (FAQ)', false);
        $response->assertSee(route('mobile.guide.download-pdf'));
    }

    /**
     * Test printable digital manual book view can be rendered and displays official DJPb document data.
     */
    public function test_user_guide_pdf_printable_manual_book_renders_correctly(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.guide.download-pdf'));

        $response->assertStatus(200);
        $response->assertSee('BUKU PANDUAN OPERASIONAL SISTEM PRESENSI & DISIPLIN', false);
        $response->assertSee('MAN-HRIS/WPB.08/2026/001', false);
        $response->assertSee('KANTOR WILAYAH PROVINSI LAMPUNG', false);
        $response->assertSee('DIREKTORAT JENDERAL PERBENDAHARAAN', false);
        $response->assertSee('Kasubbag Umum Kanwil DJPb Lampung', false);
        $response->assertSee('Disahkan Oleh', false);
    }

    /**
     * Test mobile home page displays user guide shortcut card.
     */
    public function test_mobile_home_displays_user_guide_shortcut(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.home'));

        $response->assertStatus(200);
        $response->assertSee('Panduan Pengguna & SOP', false);
        $response->assertSee(route('mobile.guide'));
    }

    /**
     * Test admin can access backoffice SystemUserGuide Filament page.
     */
    public function test_admin_can_access_filament_system_user_guide(): void
    {
        $this->actingAs($this->adminUser);
        $this->assertTrue(SystemUserGuide::canAccess());

        $response = $this->get('/admin/system-user-guide');
        $response->assertStatus(200);
        $response->assertSee('Buku Panduan Operasional Sistem');
    }

    /**
     * Test supervisor can access backoffice SystemUserGuide Filament page.
     */
    public function test_supervisor_can_access_filament_system_user_guide(): void
    {
        $this->actingAs($this->supervisorUser);
        $this->assertTrue(SystemUserGuide::canAccess());

        $response = $this->get('/admin/system-user-guide');
        $response->assertStatus(200);
        $response->assertSee('Buku Panduan Operasional Sistem');
    }

    /**
     * Test atasan can access backoffice SystemUserGuide Filament page.
     */
    public function test_atasan_can_access_filament_system_user_guide(): void
    {
        $this->actingAs($this->atasanUser);
        $this->assertTrue(SystemUserGuide::canAccess());

        $response = $this->get('/admin/system-user-guide');
        $response->assertStatus(200);
        $response->assertSee('Buku Panduan Operasional Sistem');
    }

    /**
     * Test karyawan cannot access backoffice SystemUserGuide Filament page.
     */
    public function test_karyawan_cannot_access_filament_system_user_guide(): void
    {
        $this->actingAs($this->karyawanUser);
        $this->assertFalse(SystemUserGuide::canAccess());

        $response = $this->get('/admin/system-user-guide');
        $response->assertStatus(403);
    }
}
