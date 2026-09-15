<?php

namespace Tests\Feature;

use App\Models\ContactType;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base permissions
        Permission::firstOrCreate(['name' => 'Activity Log Index', 'guard_name' => 'api', 'group_name' => 'Activity Log Permissions']);
        Permission::firstOrCreate(['name' => 'Activity Log Show', 'guard_name' => 'api', 'group_name' => 'Activity Log Permissions']);
        Permission::firstOrCreate(['name' => 'CMS Update', 'guard_name' => 'api', 'group_name' => 'CMS Permissions']);
        Permission::firstOrCreate(['name' => 'Plan Toggle Active', 'guard_name' => 'api', 'group_name' => 'Plan Permissions']);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdminRole->syncPermissions(Permission::all());
    }

    public function test_successful_login_records_activity_log(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'LOGIN',
            'module' => 'Auth',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_records_activity_log(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'FAILED_LOGIN',
            'module' => 'Auth',
        ]);
    }

    public function test_logout_records_activity_log_with_user_id(): void
    {
        $user = User::factory()->create([
            'email' => 'logoutuser@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'LOGOUT',
            'module' => 'Auth',
            'user_id' => $user->id,
        ]);
    }

    public function test_cms_update_records_activity_log_with_correct_action_and_module(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/cms/update', [
            'contents' => [
                [
                    'page' => 'home',
                    'section' => 'hero',
                    'key' => 'title',
                    'type' => 'text',
                    'value' => 'Welcome to CDP Empire',
                    'label' => 'Hero Title',
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'UPDATE',
            'module' => 'CMS',
            'user_id' => $user->id,
        ]);
    }

    public function test_plan_activation_and_deactivation_records_activity_logs(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');

        $plan = Plan::create([
            'image' => 'plans/sample.jpg',
            'maintitle' => 'Gold Plan',
            'subtitle' => 'Premium Package',
            'short_description' => 'Great features included',
            'is_active' => true,
        ]);

        $token = auth('api')->login($user);

        // Deactivate
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->patchJson("/api/v1/plans/{$plan->id}/deactivate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DEACTIVATE',
            'module' => 'Plan',
            'user_id' => $user->id,
        ]);

        // Activate
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->patchJson("/api/v1/plans/{$plan->id}/activate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'ACTIVATE',
            'module' => 'Plan',
            'user_id' => $user->id,
        ]);
    }

    public function test_contact_type_activation_records_correct_module_and_name(): void
    {
        $contactType = ContactType::create([
            'name' => 'General Inquiry',
            'code' => 'GEN-01',
            'slug' => 'general-inquiry',
            'is_active' => false,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->patchJson("/api/v1/contact-types/{$contactType->id}/activate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'ACTIVATE',
            'module' => 'Contact Type',
            'user_id' => $user->id,
        ]);
    }

    public function test_sensitive_fields_are_redacted_in_activity_log_payload(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'can_login' => true,
        ]);

        $traitUser = new class {
            use \App\Traits\ActivityLogTrait;
        };

        $traitUser->logActivity('TEST_ACTION', 'TestModule', 'Testing payload redaction', [
            'username' => 'testuser',
            'password' => 'supersecret123',
            'token' => 'jwt-token-value',
            'nested' => [
                'auth_token' => 'secret-nested-token',
                'normal_key' => 'normal_value',
            ],
        ], $user->id);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'TEST_ACTION',
            'module' => 'TestModule',
            'user_id' => $user->id,
        ]);

        $log = \App\Models\ActivityLog::where('action', 'TEST_ACTION')->first();
        $this->assertEquals('testuser', $log->payload['username']);
        $this->assertEquals('[REDACTED]', $log->payload['password']);
        $this->assertEquals('[REDACTED]', $log->payload['token']);
        $this->assertEquals('[REDACTED]', $log->payload['nested']['auth_token']);
        $this->assertEquals('normal_value', $log->payload['nested']['normal_key']);
    }
}
