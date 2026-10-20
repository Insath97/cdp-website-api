<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'Project Index', 'guard_name' => 'api', 'group_name' => 'Project Permissions']);
        Permission::firstOrCreate(['name' => 'Project Create', 'guard_name' => 'api', 'group_name' => 'Project Permissions']);
        Permission::firstOrCreate(['name' => 'Project Update', 'guard_name' => 'api', 'group_name' => 'Project Permissions']);
        Permission::firstOrCreate(['name' => 'Project Delete', 'guard_name' => 'api', 'group_name' => 'Project Permissions']);
        Permission::firstOrCreate(['name' => 'Project Toggle Active', 'guard_name' => 'api', 'group_name' => 'Project Permissions']);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdminRole->syncPermissions(Permission::all());
    }

    private function authenticateSuperAdmin(): string
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        return auth('api')->login($user);
    }

    public function test_guest_cannot_access_protected_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);
    }

    public function test_super_admin_can_create_project(): void
    {
        $token = $this->authenticateSuperAdmin();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/projects', [
            'title' => 'Empire City Heights',
            'short_description' => 'Luxury residential complex in Colombo',
            'description' => 'Full details about Empire City Heights project.',
            'status' => 'upcoming',
            'location' => 'Colombo 03',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.title', 'Empire City Heights');
        $response->assertJsonPath('data.slug', 'empire-city-heights');
        $response->assertJsonPath('data.status', 'upcoming');

        $this->assertDatabaseHas('projects', [
            'title' => 'Empire City Heights',
            'slug' => 'empire-city-heights',
            'status' => 'upcoming',
            'location' => 'Colombo 03',
        ]);
    }

    public function test_super_admin_can_update_project(): void
    {
        $token = $this->authenticateSuperAdmin();

        $project = Project::create([
            'title' => 'Marina View Phase 1',
            'slug' => 'marina-view-phase-1',
            'short_description' => 'Initial phase',
            'description' => 'Detailed description',
            'status' => 'upcoming',
            'location' => 'Galle',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/projects/{$project->id}", [
            'title' => 'Marina View Phase 1 Updated',
            'status' => 'open',
            'location' => 'Galle Fort',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.title', 'Marina View Phase 1 Updated');
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.location', 'Galle Fort');
    }

    public function test_super_admin_can_toggle_project_active_status(): void
    {
        $token = $this->authenticateSuperAdmin();

        $project = Project::create([
            'title' => 'Ocean Towers',
            'slug' => 'ocean-towers',
            'status' => 'open',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->patchJson("/api/v1/projects/{$project->id}/toggle-status");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.is_active', false);
    }

    public function test_super_admin_can_delete_project(): void
    {
        $token = $this->authenticateSuperAdmin();

        $project = Project::create([
            'title' => 'Green Valley',
            'slug' => 'green-valley',
            'status' => 'completed',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_public_can_list_active_projects(): void
    {
        Project::create([
            'title' => 'Active Project 1',
            'slug' => 'active-project-1',
            'status' => 'open',
            'location' => 'Colombo',
            'is_active' => true,
        ]);

        Project::create([
            'title' => 'Active Project 2',
            'slug' => 'active-project-2',
            'status' => 'upcoming',
            'location' => 'Kandy',
            'is_active' => true,
        ]);

        Project::create([
            'title' => 'Inactive Project',
            'slug' => 'inactive-project',
            'status' => 'open',
            'location' => 'Negombo',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/public/projects');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.total', 2);
    }

    public function test_public_can_filter_projects_by_status(): void
    {
        Project::create([
            'title' => 'Project Upcoming',
            'slug' => 'project-upcoming',
            'status' => 'upcoming',
            'is_active' => true,
        ]);

        Project::create([
            'title' => 'Project Open',
            'slug' => 'project-open',
            'status' => 'open',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/public/projects?status=upcoming');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.slug', 'project-upcoming');
    }

    public function test_public_can_view_single_project_by_id_or_slug(): void
    {
        $project = Project::create([
            'title' => 'Skyline Heights',
            'slug' => 'skyline-heights',
            'status' => 'completed',
            'location' => 'Battaramulla',
            'is_active' => true,
        ]);

        // By slug
        $responseSlug = $this->getJson('/api/v1/public/projects/skyline-heights');
        $responseSlug->assertStatus(200);
        $responseSlug->assertJsonPath('data.id', $project->id);

        // By ID
        $responseId = $this->getJson("/api/v1/public/projects/{$project->id}");
        $responseId->assertStatus(200);
        $responseId->assertJsonPath('data.slug', 'skyline-heights');
    }
}
