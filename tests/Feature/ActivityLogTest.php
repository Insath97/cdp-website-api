<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        Permission::findOrCreate('Activity Log Index', 'api');
        Permission::findOrCreate('Activity Log Show', 'api');
        
        $role = Role::findOrCreate('Super Admin', 'api');
        $role->givePermissionTo(['Activity Log Index', 'Activity Log Show']);
    }

    public function test_guests_cannot_access_activity_logs(): void
    {
        $response = $this->getJson('/api/v1/activity-logs');
        $response->assertStatus(401);
    }

    public function test_users_without_permission_cannot_access_activity_logs(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(403);
    }

    public function test_users_with_permission_can_access_activity_logs(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
            'username' => 'testuser',
        ]);
        $user->assignRole('Super Admin');

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE',
            'module' => 'User',
            'description' => 'Created user',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/v1/activity-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'action',
                            'module',
                            'description',
                            'ip_address',
                            'user_agent',
                            'created_at',
                            'user' => [
                                'id',
                                'name',
                                'email',
                                'profile_image',
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
