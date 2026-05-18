<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Career;
use App\Models\Responsibility;
use App\Models\Requirement;
use App\Models\Benefit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CareerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions safely
        Permission::findOrCreate('Career Index', 'api');
        Permission::findOrCreate('Career Create', 'api');
        Permission::findOrCreate('Career Update', 'api');
        Permission::findOrCreate('Career Delete', 'api');
        Permission::findOrCreate('Career Soft Delete', 'api');
        Permission::findOrCreate('Career Force Delete', 'api');
        Permission::findOrCreate('Career Restore', 'api');
        Permission::findOrCreate('Career Toggle Active', 'api');
        
        $role = Role::findOrCreate('Super Admin', 'api');
        $role->givePermissionTo([
            'Career Index',
            'Career Create',
            'Career Update',
            'Career Delete',
            'Career Soft Delete',
            'Career Force Delete',
            'Career Restore',
            'Career Toggle Active'
        ]);
    }

    public function test_guests_cannot_access_admin_endpoints(): void
    {
        $response = $this->getJson('/api/v1/careers');
        $response->assertStatus(401);
    }

    public function test_unauthorized_users_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/v1/careers');

        $response->assertStatus(403);
    }

    public function test_admin_can_perform_crud_actions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');
        $token = auth('api')->login($user);

        $payload = [
            'title' => 'Senior Backend Developer',
            'description' => 'We are seeking a talented backend dev.',
            'department' => 'Sales',
            'location' => 'Island-wide',
            'job_type' => 'Full-time',
            'due_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
            'key_responsibilities' => [
                'Write clean code',
                'Develop microservices'
            ],
            'requirements' => [
                'Laravel experience',
                'MySQL knowledge'
            ],
            'benefits' => [
                'ETF/EPF benefits',
                'Flexible working hours'
            ]
        ];

        // 1. Create Career Post
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/v1/careers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Senior Backend Developer')
            ->assertJsonPath('data.department', 'Sales')
            ->assertJsonPath('data.location', 'Island-wide')
            ->assertJsonPath('data.job_type', 'Full-time')
            ->assertJsonPath('data.slug', 'senior-backend-developer');

        $careerId = $response->json('data.id');

        // Assert database attributes and relationships are saved
        $this->assertDatabaseHas('careers', [
            'id' => $careerId,
            'title' => 'Senior Backend Developer',
            'department' => 'Sales',
            'location' => 'Island-wide',
            'job_type' => 'Full-time'
        ]);
        $this->assertDatabaseHas('responsibilities', ['name' => 'Write clean code']);
        $this->assertDatabaseHas('requirements', ['name' => 'Laravel experience']);
        $this->assertDatabaseHas('benefits', ['name' => 'ETF/EPF benefits']);

        // 2. Read Career Post Detail
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/v1/careers/{$careerId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.responsibilities')
            ->assertJsonCount(2, 'data.requirements')
            ->assertJsonCount(2, 'data.benefits');

        // 3. Update Career Post
        $updatePayload = [
            'title' => 'Lead Backend Engineer',
            'department' => 'Engineering',
            'location' => 'Remote',
            'job_type' => 'Contract',
            'key_responsibilities' => [
                'Lead the developer team',
                'Design DB schemas'
            ]
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/v1/careers/{$careerId}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Lead Backend Engineer')
            ->assertJsonPath('data.department', 'Engineering')
            ->assertJsonPath('data.location', 'Remote')
            ->assertJsonPath('data.job_type', 'Contract');

        $this->assertDatabaseHas('careers', [
            'id' => $careerId,
            'department' => 'Engineering',
            'location' => 'Remote',
            'job_type' => 'Contract'
        ]);

        // Check if responsibilities were successfully synced (old ones removed, new ones synced)
        $this->assertDatabaseHas('responsibilities', ['name' => 'Lead the developer team']);

        // 4. Toggle Status
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->patchJson("/api/v1/careers/{$careerId}/toggle-status");
        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 5. Soft Delete
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/v1/careers/{$careerId}");
        $response->assertStatus(200);
        $this->assertSoftDeleted('careers', ['id' => $careerId]);

        // 6. Restore Soft Deleted Post
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->patchJson("/api/v1/careers/{$careerId}/restore");
        $response->assertStatus(200);
        $this->assertDatabaseHas('careers', ['id' => $careerId, 'deleted_at' => null]);

        // 7. Force Delete
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->deleteJson("/api/v1/careers/{$careerId}/force-delete");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('careers', ['id' => $careerId]);
    }

    public function test_public_endpoints_enforce_active_and_expiration_rules(): void
    {
        // 1. Create an active unexpired career post
        $activePost = Career::create([
            'title' => 'Junior Frontend Dev',
            'slug' => 'junior-frontend-dev',
            'department' => 'Sales',
            'location' => 'Island-wide',
            'job_type' => 'Full-time',
            'due_date' => now()->addDays(5)->toDateString(),
            'is_active' => true,
        ]);

        // 2. Create an inactive career post
        $inactivePost = Career::create([
            'title' => 'Inactive Database Admin',
            'slug' => 'inactive-database-admin',
            'department' => 'Sales',
            'location' => 'Island-wide',
            'job_type' => 'Full-time',
            'due_date' => now()->addDays(5)->toDateString(),
            'is_active' => false,
        ]);

        // 3. Create an expired career post
        $expiredPost = Career::create([
            'title' => 'Expired DevOps Engineer',
            'slug' => 'expired-devops-engineer',
            'department' => 'Sales',
            'location' => 'Island-wide',
            'job_type' => 'Full-time',
            'due_date' => now()->subDays(2)->toDateString(),
            'is_active' => true,
        ]);

        // Retrieve public list
        $response = $this->getJson('/api/v1/public/careers');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Junior Frontend Dev'])
            ->assertJsonMissing(['title' => 'Inactive Database Admin'])
            ->assertJsonMissing(['title' => 'Expired DevOps Engineer']);

        // Test filtering capability
        $this->getJson('/api/v1/public/careers?department=Sales')->assertJsonFragment(['title' => 'Junior Frontend Dev']);
        $this->getJson('/api/v1/public/careers?department=Engineering')->assertJsonMissing(['title' => 'Junior Frontend Dev']);

        // Retrieve public details
        // Active should pass
        $this->getJson("/api/v1/public/careers/{$activePost->id}")->assertStatus(200);

        // Inactive should fail (404)
        $this->getJson("/api/v1/public/careers/{$inactivePost->id}")->assertStatus(404);

        // Expired should fail (404)
        $this->getJson("/api/v1/public/careers/{$expiredPost->id}")->assertStatus(404);
    }
}
