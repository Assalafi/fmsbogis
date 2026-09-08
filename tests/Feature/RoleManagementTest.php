<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        app('url')->forceRootUrl('http://localhost');
        $this->seed(RolesAndPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_finance_admin_can_create_a_dynamic_role_with_required_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Finance Admin');

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Treasury Supervisor',
            'permissions' => [
                'payments.create',
                'reports.export',
            ],
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHasNoErrors();

        $role = Role::findByName('Treasury Supervisor');
        $this->assertTrue($role->hasAllPermissions([
            'dashboard.view',
            'payments.view',
            'payments.create',
            'reports.view',
            'reports.export',
        ]));
    }

    public function test_role_can_be_renamed_and_its_permissions_updated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Finance Admin');
        $role = Role::create(['name' => 'Temporary Role', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');

        $response = $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Cashbook Reviewer',
            'permissions' => ['cashbook.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHasNoErrors();
        $role->refresh();

        $this->assertSame('Cashbook Reviewer', $role->name);
        $this->assertTrue($role->hasAllPermissions(['dashboard.view', 'cashbook.view']));
    }

    public function test_administrator_cannot_grant_permissions_above_their_access_level(): void
    {
        $roleManager = Role::create(['name' => 'Role Manager', 'guard_name' => 'web']);
        $roleManager->givePermissionTo([
            'dashboard.view',
            'roles.view',
            'roles.create',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($roleManager);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Escalated Role',
            'permissions' => ['payments.approve'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('permissions.0');
        $this->assertDatabaseMissing('roles', ['name' => 'Escalated Role']);
    }

    public function test_super_admin_role_is_protected_from_changes_and_deletion(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $superAdminRole = Role::findByName('Super Admin');

        $this->actingAs($admin)
            ->put(route('roles.update', $superAdminRole), [
                'name' => 'Changed Admin',
                'permissions' => [],
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('roles.destroy', $superAdminRole))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
    }

    public function test_role_assigned_to_a_user_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $role = Role::create(['name' => 'Assigned Role', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        User::factory()->create()->assignRole($role);

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['name' => 'Assigned Role']);
    }

    public function test_roles_page_shows_grouped_permission_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Create Role')
            ->assertSee('Available Permissions')
            ->assertSee('Edit Role');
    }

    public function test_finance_admin_sees_the_complete_permission_catalogue_on_create_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Finance Admin');
        $permissionCount = collect(config('finance_permissions.groups'))
            ->sum(fn (array $group) => count($group));

        $response = $this->actingAs($admin)->get(route('roles.create'));

        $response->assertOk()
            ->assertSee('Select All Permissions')
            ->assertSee('Clear All')
            ->assertSee($permissionCount.' permissions available')
            ->assertSee('Budgets Sync')
            ->assertDontSee('Virements Cross Type');

        collect(config('finance_permissions.groups'))
            ->flatMap(fn (array $group) => array_keys($group))
            ->each(fn (string $permission) => $response->assertSee('value="'.$permission.'"', false));
    }

    public function test_edit_page_shows_the_complete_matrix_and_existing_selection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $role = Role::create(['name' => 'Editable Reviewer', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'cashbook.view']);

        $response = $this->actingAs($admin)->get(route('roles.edit', $role));

        $response->assertOk()
            ->assertSee('Select All Permissions')
            ->assertSee('Clear All')
            ->assertSee('value="cashbook.view"', false);
        $this->assertMatchesRegularExpression(
            '/value="cashbook\.view"[\s\S]*?checked/',
            $response->getContent(),
        );
    }

    public function test_permission_catalogue_and_database_are_synchronised(): void
    {
        $catalogued = collect(config('finance_permissions.groups'))
            ->flatMap(fn (array $group) => array_keys($group))
            ->sort()
            ->values();
        $stored = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->sort()
            ->values();

        $this->assertSame($catalogued->all(), $stored->all());
        $this->assertTrue(Role::findByName('Super Admin')->hasAllPermissions($catalogued));
        $this->assertTrue(Role::findByName('Finance Admin')->hasAllPermissions([
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ]));
    }

    public function test_a_dynamic_role_is_immediately_available_when_creating_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $role = Role::create(['name' => 'Custom Cashier', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboard.view', 'receipts.view', 'receipts.create']);

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Custom Cashier');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Cashier',
            'email' => 'cashier@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'Custom Cashier',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue(User::where('email', 'cashier@example.test')->firstOrFail()->hasRole('Custom Cashier'));
    }

    public function test_report_export_requires_the_export_permission(): void
    {
        $viewerRole = Role::create(['name' => 'Report Viewer Only', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo(['dashboard.view', 'reports.view']);
        $viewer = User::factory()->create();
        $viewer->assignRole($viewerRole);

        $this->actingAs($viewer)
            ->get(route('reports.show', ['report' => 'budget-report', 'export' => 'excel']))
            ->assertForbidden();
    }
}
