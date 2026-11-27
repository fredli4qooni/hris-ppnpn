<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('Potong Foto Profil');
        $response->assertSee('Simpan Foto');
        $response->assertDontSee('3.200.000'); // Salary must not be displayed to employee
        $response->assertDontSee('Honorarium Bulanan');
        $response->assertDontSee('Rp ');
    }

    /**
     * Test profile displays readiness status and accurate standard working hours (Milestone 1).
     */
    public function test_profile_displays_presence_readiness_and_working_hours(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/profile');

        $response->assertSuccessful();
        $response->assertSee('Siap Presensi');
        $response->assertSee('Geofence + Liveness');
        $response->assertDontSee('Belum Terdaftar');

        $expectedStart = substr($this->employee->position->default_start_time, 0, 5);
        $expectedEnd = substr($this->employee->position->default_end_time, 0, 5);
        $response->assertSee("{$expectedStart} -");
        $response->assertSee("{$expectedEnd} WIB");
    }

    /**
     * Test mobile home does not contain the standalone profile shortcut button (Milestone 3 - Poin 3).
     */
    public function test_mobile_home_does_not_contain_profile_shortcut_button(): void
    {
        $response = $this->actingAs($this->karyawanUser)
            ->get('/mobile/home');

        $response->assertSuccessful();
        $response->assertSee('Ahmad Subari');
        $response->assertDontSee('Lihat Profil Lengkap Mandiri');
        $response->assertDontSee('Rp ');
    }

    /**
     * Test authenticated employee can upload, replace, and display profile photo.
     */
    public function test_employee_can_upload_and_replace_profile_photo(): void
    {
        Storage::fake('public');

        $firstFile = UploadedFile::fake()->image('avatar-first.jpg', 300, 300);

        $response = $this->actingAs($this->karyawanUser)
            ->post(route('mobile.profile.photo.update'), [
                'photo' => $firstFile,
            ]);

        $response->assertRedirect(route('mobile.profile'));
        $response->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

        $this->employee->refresh();
        $firstPath = $this->employee->photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $profilePage = $this->actingAs($this->karyawanUser)->get(route('mobile.profile'));
        $profilePage->assertSuccessful();
        $profilePage->assertSee('/storage/'.$firstPath);
        $profilePage->assertSee('Ganti Foto');
        $profilePage->assertSee('Hapus Foto');

        $homePage = $this->actingAs($this->karyawanUser)->get(route('mobile.home'));
        $homePage->assertSuccessful();
        $homePage->assertSee('/storage/'.$firstPath);

        // Replace with a second photo and verify old file cleanup
        $secondFile = UploadedFile::fake()->image('avatar-second.png', 250, 250);

        $this->actingAs($this->karyawanUser)
            ->post(route('mobile.profile.photo.update'), [
                'photo' => $secondFile,
            ])
            ->assertRedirect(route('mobile.profile'));

        $this->employee->refresh();
        $secondPath = $this->employee->photo_path;
        $this->assertNotNull($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    /**
     * Test invalid profile photo upload fails validation.
     */
    public function test_profile_photo_upload_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->karyawanUser)
            ->from(route('mobile.profile'))
            ->post(route('mobile.profile.photo.update'), [
                'photo' => $invalidFile,
            ]);

        $response->assertRedirect(route('mobile.profile'));
        $response->assertSessionHasErrors('photo');
    }

    /**
     * Test authenticated employee can delete their profile photo.
     */
    public function test_employee_can_delete_profile_photo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $this->actingAs($this->karyawanUser)
            ->post(route('mobile.profile.photo.update'), ['photo' => $file]);

        $this->employee->refresh();
        $storedPath = $this->employee->photo_path;
        $this->assertNotNull($storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $response = $this->actingAs($this->karyawanUser)
            ->delete(route('mobile.profile.photo.destroy'));

        $response->assertRedirect(route('mobile.profile'));
        $response->assertSessionHas('success', 'Foto profil berhasil dihapus.');

        $this->employee->refresh();
        $this->assertNull($this->employee->photo_path);
        Storage::disk('public')->assertMissing($storedPath);

        $profilePage = $this->actingAs($this->karyawanUser)->get(route('mobile.profile'));
        $profilePage->assertSuccessful();
        $profilePage->assertSee('Unggah Foto');
        $profilePage->assertDontSee('Hapus Foto');
    }
}
