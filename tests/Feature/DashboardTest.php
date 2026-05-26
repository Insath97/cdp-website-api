<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Branch;
use App\Models\ContactType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $dashboardPermission = Permission::firstOrCreate([
            'name' => 'Dashboard Index',
            'group_name' => 'Access Management Permissions',
            'guard_name' => 'api'
        ]);

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'api'
        ]);
        $superAdminRole->syncPermissions([$dashboardPermission]);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(401);
    }

    public function test_unauthorized_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/dashboard');

        $response->assertStatus(403);
    }

    public function test_authorized_super_admin_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true
        ]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        // Seed some data
        ContactType::create([
            'name' => 'General Inquiry',
            'slug' => 'general-inquiry',
            'code' => 'INQ',
            'is_active' => true
        ]);

        ContactType::create([
            'name' => 'Sales Lead',
            'slug' => 'sales-lead',
            'code' => 'LEAD',
            'is_active' => true
        ]);

        Branch::create([
            'name' => 'Colombo Branch',
            'code' => 'CMB',
            'address' => 'Colombo, Sri Lanka',
            'city' => 'Colombo',
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'cards' => [
                        'total_users',
                        'total_contacts',
                        'published_blogs',
                        'total_branches',
                    ],
                    'user_growth' => [
                        'labels',
                        'data',
                    ],
                    'weekly_activity' => [
                        'labels',
                        'inquiries',
                        'leads',
                    ],
                ]
            ]);
    }
}
