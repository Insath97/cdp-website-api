<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'Branch Index', 'guard_name' => 'api', 'group_name' => 'Branch Permissions']);
        Permission::firstOrCreate(['name' => 'Branch Create', 'guard_name' => 'api', 'group_name' => 'Branch Permissions']);
        Permission::firstOrCreate(['name' => 'Branch Update', 'guard_name' => 'api', 'group_name' => 'Branch Permissions']);
        Permission::firstOrCreate(['name' => 'Branch Delete', 'guard_name' => 'api', 'group_name' => 'Branch Permissions']);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdminRole->syncPermissions(Permission::all());
    }

    private function authenticateSuperAdmin(): string
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        return auth('api')->login($user);
    }

    public function test_guest_cannot_access_protected_branches(): void
    {
        $response = $this->getJson('/api/v1/branches');
        $response->assertStatus(401);
    }

    public function test_super_admin_can_create_branch_with_phone_and_description(): void
    {
        $token = $this->authenticateSuperAdmin();

        $payload = [
            'name' => 'Colombo Main Branch',
            'code' => 'CMB-001',
            'address' => '123 Galle Road',
            'city' => 'Colombo',
            'phone' => '+94 11 234 5678',
            'description' => 'Flagship branch in central Colombo.',
            'is_active' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/branches', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.phone', '+94 11 234 5678');
        $response->assertJsonPath('data.description', 'Flagship branch in central Colombo.');

        $this->assertDatabaseHas('branches', [
            'code' => 'CMB-001',
            'phone' => '+94 11 234 5678',
            'description' => 'Flagship branch in central Colombo.',
        ]);
    }

    public function test_super_admin_can_create_branch_without_phone_and_description(): void
    {
        $token = $this->authenticateSuperAdmin();

        $payload = [
            'name' => 'Kandy Branch',
            'code' => 'KDY-001',
            'address' => '45 Peradeniya Road',
            'city' => 'Kandy',
            'is_active' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/branches', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.phone', null);
        $response->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('branches', [
            'code' => 'KDY-001',
            'phone' => null,
            'description' => null,
        ]);
    }

    public function test_super_admin_can_update_branch_phone_and_description(): void
    {
        $token = $this->authenticateSuperAdmin();

        $branch = Branch::create([
            'name' => 'Galle Branch',
            'code' => 'GLE-001',
            'address' => '89 Fort Road',
            'city' => 'Galle',
            'phone' => null,
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/branches/{$branch->id}", [
            'name' => 'Galle Fort Branch',
            'code' => 'GLE-001',
            'address' => '89 Fort Road',
            'city' => 'Galle',
            'phone' => '+94 91 223 4567',
            'description' => 'Updated description for Galle Fort branch.',
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.phone', '+94 91 223 4567');
        $response->assertJsonPath('data.description', 'Updated description for Galle Fort branch.');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Galle Fort Branch',
            'phone' => '+94 91 223 4567',
            'description' => 'Updated description for Galle Fort branch.',
        ]);
    }

    public function test_search_scope_filters_by_phone_and_description(): void
    {
        $token = $this->authenticateSuperAdmin();

        Branch::create([
            'name' => 'Branch A',
            'code' => 'BRA-001',
            'address' => 'Address A',
            'city' => 'Jaffna',
            'phone' => '0212223344',
            'description' => 'Northern regional headquarters',
            'is_active' => true,
        ]);

        Branch::create([
            'name' => 'Branch B',
            'code' => 'BRB-002',
            'address' => 'Address B',
            'city' => 'Matara',
            'phone' => '0415556677',
            'description' => 'Southern coastal office',
            'is_active' => true,
        ]);

        // Search by phone
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/branches?search=0212223344');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.code', 'BRA-001');

        // Search by description
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/branches?search=coastal');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.code', 'BRB-002');
    }

    public function test_public_can_list_branches_with_phone_and_description(): void
    {
        Branch::create([
            'name' => 'Negombo Branch',
            'code' => 'NEG-001',
            'address' => '12 Sea Street',
            'city' => 'Negombo',
            'phone' => '+94 31 222 1100',
            'description' => 'Beachside service branch',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/branches');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonFragment([
            'name' => 'Negombo Branch',
            'code' => 'NEG-001',
            'city' => 'Negombo',
            'phone' => '+94 31 222 1100',
            'description' => 'Beachside service branch',
        ]);
    }
}
