<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeadershipMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base permissions
        Permission::firstOrCreate(['name' => 'Leadership Index', 'guard_name' => 'api', 'group_name' => 'Leadership Permissions']);
        Permission::firstOrCreate(['name' => 'Leadership Create', 'guard_name' => 'api', 'group_name' => 'Leadership Permissions']);
        Permission::firstOrCreate(['name' => 'Leadership Update', 'guard_name' => 'api', 'group_name' => 'Leadership Permissions']);
        Permission::firstOrCreate(['name' => 'Leadership Delete', 'guard_name' => 'api', 'group_name' => 'Leadership Permissions']);
        Permission::firstOrCreate(['name' => 'Leadership Toggle Active', 'guard_name' => 'api', 'group_name' => 'Leadership Permissions']);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdminRole->syncPermissions(Permission::all());
    }

    public function test_guest_cannot_access_leadership_members(): void
    {
        $response = $this->getJson('/api/v1/leadership-members');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_access_leadership_members(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/leadership-members');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_list_leadership_members(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        LeadershipMember::create([
            'section' => 'chairman',
            'name' => 'A. R. Wickramasinghe',
            'designation' => 'Founder & Executive Chairman',
            'message' => 'A message from our Chairman',
            'sort_order' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/leadership-members');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.section', 'chairman');
    }

    public function test_super_admin_can_create_leadership_member_with_relations(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/leadership-members', [
            'section' => 'executive',
            'name' => 'Dr. Sanjeewa Bandara',
            'designation' => 'Group Chief Executive Officer & Managing Director',
            'group_label' => 'Board & Executive Leadership',
            'executive_statement' => 'Translating board governance into operational rigor.',
            'focus_area' => 'Enterprise Growth & Operational Discipline',
            'sort_order' => 1,
            'is_active' => true,
            'qualifications' => ['B.Sc. Agric (Hons)', 'M.Sc.', 'Ph.D. (Plantation Economics)'],
            'linkedin' => 'https://linkedin.com/in/example',
        ]);

        $response->assertStatus(201);

        $member = LeadershipMember::where('name', 'Dr. Sanjeewa Bandara')->first();
        $this->assertNotNull($member);
        $this->assertCount(3, $member->qualifications);
        $this->assertEquals('https://linkedin.com/in/example', $member->linkedin);
    }

    public function test_leadership_member_store_requires_designation(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/leadership-members', [
            'section' => 'executive',
            'name' => 'Missing Designation',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('leadership_members', ['name' => 'Missing Designation']);
    }

    public function test_super_admin_can_update_leadership_member_and_replace_relations(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $member = LeadershipMember::create([
            'section' => 'executive',
            'name' => 'Rohan Senanayake',
            'designation' => 'Country Manager',
            'linkedin' => 'https://linkedin.com/in/old-rohan',
            'sort_order' => 2,
        ]);
        $member->qualifications()->create(['title' => 'Old Qualification', 'sort_order' => 0]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/leadership-members/{$member->id}", [
            'designation' => 'Country Manager - Updated',
            'qualifications' => ['B.Sc. Plantation Management', 'MBA (Colombo)', 'PMP'],
            'linkedin' => 'https://linkedin.com/in/rohan',
        ]);

        $response->assertStatus(200);

        $member->refresh();
        $this->assertEquals('Country Manager - Updated', $member->designation);
        $this->assertCount(3, $member->qualifications);
        $this->assertEquals('https://linkedin.com/in/rohan', $member->linkedin);
        $this->assertDatabaseMissing('leadership_qualifications', ['title' => 'Old Qualification']);
    }

    public function test_super_admin_can_toggle_leadership_member_status(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $member = LeadershipMember::create([
            'section' => 'specialised',
            'name' => 'Nirosha Karunaratne',
            'designation' => 'Group Head of Sustainability & ESG',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->patchJson("/api/v1/leadership-members/{$member->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertFalse($member->refresh()->is_active);
    }

    public function test_super_admin_can_delete_leadership_member_with_relations(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $member = LeadershipMember::create([
            'section' => 'specialised',
            'name' => 'Dilrukshi Perera',
            'designation' => 'Group Head of Human Resources & People Development',
        ]);
        $member->qualifications()->create(['title' => 'Chartered CIPM', 'sort_order' => 0]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/v1/leadership-members/{$member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('leadership_members', ['id' => $member->id]);
        $this->assertDatabaseMissing('leadership_qualifications', ['leadership_member_id' => $member->id]);
    }
}
