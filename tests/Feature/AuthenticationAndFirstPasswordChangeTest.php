<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndFirstPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test the login page renders successfully.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Kanwil DJPb Lampung');
        $response->assertSee('Masuk ke Akun Anda');
        $response->assertSee('images/logo-djpb.png');
    }

    /**
     * Test karyawan can authenticate using username.
     */
    public function test_karyawan_can_authenticate_using_username(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();
        $karyawan->update(['must_change_password' => false]);

        $response = $this->post('/login', [
            'login' => 'ppnpn01',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/mobile/home');

        $karyawan->refresh();
        $this->assertNotNull($karyawan->last_login_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $karyawan->id,
            'action' => 'LOGIN',
        ]);
    }

    /**
     * Test karyawan can authenticate using email.
     */
    public function test_karyawan_can_authenticate_using_email(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();
        $karyawan->update(['must_change_password' => false]);

        $response = $this->post('/login', [
            'login' => 'ahmad.subari@djpb.kemenkeu.go.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/mobile/home');
    }

    /**
     * Test admin credentials are prevented from logging in via employee login form.
     */
    public function test_admin_and_officials_cannot_login_through_employee_login_form(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Akses Ditolak', session('errors')->first('login'));
    }

    /**
     * Test inactive users are prevented from authenticating.
     */
    public function test_inactive_users_cannot_authenticate(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();
        $karyawan->update(['is_active' => false]);

        $response = $this->post('/login', [
            'login' => 'ppnpn01',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    /**
     * Test authentication fails with wrong password.
     */
    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login' => 'ppnpn01',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGIN_FAILED',
        ]);
    }

    /**
     * Test rate limiting triggers after 5 failed attempts.
     */
    public function test_login_rate_limiting_triggers_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'login' => 'ppnpn01',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be blocked by rate limiter
        $response = $this->post('/login', [
            'login' => 'ppnpn01',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan gagal', session('errors')->first('login'));
    }

    /**
     * Test user with must_change_password=true is redirected to first-change form.
     */
    public function test_user_with_must_change_password_is_redirected_to_first_change_page(): void
    {
        $response = $this->post('/login', [
            'login' => 'ppnpn01',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('password.first-change'));
    }

    /**
     * Test user with must_change_password=true is locked out of other routes by middleware.
     */
    public function test_user_with_must_change_password_is_locked_by_middleware(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        // Attempt accessing mobile home while must_change_password is true
        $response = $this->actingAs($karyawan)->get('/mobile/home');

        $response->assertRedirect(route('password.first-change'));
    }

    /**
     * Test user can successfully change temporary password and continue.
     */
    public function test_user_can_change_temporary_password_successfully(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        $response = $this->actingAs($karyawan)
            ->post('/password/first-change', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect('/mobile/home');
        $response->assertSessionHas('success');

        $karyawan->refresh();
        $this->assertFalse($karyawan->must_change_password);
        $this->assertTrue(Hash::check('NewPassword123!', $karyawan->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $karyawan->id,
            'action' => 'PASSWORD_CHANGED',
        ]);
    }

    /**
     * Test password change validation fails if criteria are not met.
     */
    public function test_password_change_validation_rejects_weak_or_same_passwords(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        // 1. Same as current password
        $responseSame = $this->actingAs($karyawan)
            ->post('/password/first-change', [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        $responseSame->assertSessionHasErrors('password');

        // 2. Under 8 characters
        $responseShort = $this->actingAs($karyawan)
            ->post('/password/first-change', [
                'current_password' => 'password',
                'password' => 'short1',
                'password_confirmation' => 'short1',
            ]);
        $responseShort->assertSessionHasErrors('password');

        // 3. Letters only without numbers
        $responseNoNumber = $this->actingAs($karyawan)
            ->post('/password/first-change', [
                'current_password' => 'password',
                'password' => 'alllettershere',
                'password_confirmation' => 'alllettershere',
            ]);
        $responseNoNumber->assertSessionHasErrors('password');
    }

    /**
     * Test user can log out securely.
     */
    public function test_user_can_logout(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();

        $response = $this->actingAs($karyawan)
            ->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $karyawan->id,
            'action' => 'LOGOUT',
        ]);
    }

    /**
     * Test authenticated karyawan visiting root or login is redirected cleanly without loop.
     */
    public function test_authenticated_karyawan_visiting_root_or_login_is_redirected_to_mobile_home(): void
    {
        $karyawan = User::where('username', 'ppnpn01')->first();
        $karyawan->update(['must_change_password' => false]);

        $responseRoot = $this->actingAs($karyawan)->get('/');
        $responseRoot->assertRedirect('/mobile/home');

        $responseLogin = $this->actingAs($karyawan)->get('/login');
        $responseLogin->assertRedirect('/mobile/home');
    }

    /**
     * Test authenticated admin visiting root or login is redirected cleanly to backoffice.
     */
    public function test_authenticated_admin_visiting_root_or_login_is_redirected_to_admin(): void
    {
        $admin = User::where('username', 'admin')->first();

        $responseRoot = $this->actingAs($admin)->get('/');
        $responseRoot->assertRedirect('/admin');

        $responseLogin = $this->actingAs($admin)->get('/login');
        $responseLogin->assertRedirect('/admin');
    }
}
