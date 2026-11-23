<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementResourceTest extends TestCase
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
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->supervisorUser = User::where('username', 'supervisor')->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->firstOrFail();
        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
    }

    public function test_admin_can_access_user_resource_index(): void
    {
        $this->actingAs($this->adminUser);

        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(UserResource::canViewAny());

        $response = $this->get('/admin/users');
        $response->assertOk();

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertSee($this->adminUser->name)
            ->assertSee($this->supervisorUser->name);
    }

    public function test_non_admin_roles_are_forbidden_from_user_resource(): void
    {
        // Supervisor forbidden
        $this->actingAs($this->supervisorUser);
        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(UserResource::canViewAny());
        $response = $this->get('/admin/users');
        $this->assertContains($response->status(), [302, 403]);

        // Atasan forbidden
        $this->actingAs($this->atasanUser);
        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(UserResource::canViewAny());
        $response = $this->get('/admin/users');
        $this->assertContains($response->status(), [302, 403]);

        // Karyawan forbidden
        $this->actingAs($this->karyawanUser);
        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(UserResource::canViewAny());
        $response = $this->get('/admin/users');
        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_admin_can_create_a_new_user_with_role(): void
    {
        $this->actingAs($this->adminUser);

        $role = Role::where('name', 'supervisor')->firstOrFail();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Koordinator Baru',
                'username' => 'koordinator.baru',
                'email' => 'koordinator@kemenkeu.go.id',
                'password' => 'password123',
                'roles' => [$role->id],
                'is_active' => true,
                'must_change_password' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'koordinator.baru',
            'name' => 'Koordinator Baru',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $createdUser = User::where('username', 'koordinator.baru')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('supervisor'));
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_admin_can_update_user_details(): void
    {
        $this->actingAs($this->adminUser);

        $targetUser = $this->supervisorUser;

        Livewire::test(EditUser::class, [
            'record' => $targetUser->getKey(),
        ])
            ->fillForm([
                'name' => 'Supervisor Diperbarui',
                'email' => 'supervisor.updated@kemenkeu.go.id',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Supervisor Diperbarui',
            'email' => 'supervisor.updated@kemenkeu.go.id',
        ]);
    }

    public function test_admin_can_reset_user_password_via_table_action(): void
    {
        $this->actingAs($this->adminUser);

        $targetUser = $this->karyawanUser;

        Livewire::test(ListUsers::class)
            ->callTableAction('resetPassword', $targetUser, [
                'new_password' => 'newSecretPass99',
                'must_change_password' => true,
            ])
            ->assertHasNoTableActionErrors();

        $targetUser->refresh();
        $this->assertTrue(Hash::check('newSecretPass99', $targetUser->password));
        $this->assertTrue($targetUser->must_change_password);
    }

    public function test_admin_can_toggle_user_active_status_via_table_action(): void
    {
        $this->actingAs($this->adminUser);

        $targetUser = $this->karyawanUser;
        $this->assertTrue($targetUser->is_active);

        Livewire::test(ListUsers::class)
            ->callTableAction('toggleActive', $targetUser)
            ->assertHasNoTableActionErrors();

        $targetUser->refresh();
        $this->assertFalse($targetUser->is_active);

        // Toggle back to active
        Livewire::test(ListUsers::class)
            ->callTableAction('toggleActive', $targetUser)
            ->assertHasNoTableActionErrors();

        $targetUser->refresh();
        $this->assertTrue($targetUser->is_active);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->adminUser);

        $this->assertFalse(UserResource::canDelete($this->adminUser));

        // Can delete another user
        $this->assertTrue(UserResource::canDelete($this->karyawanUser));
    }
}
