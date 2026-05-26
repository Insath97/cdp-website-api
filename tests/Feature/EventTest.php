<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base permissions
        Permission::firstOrCreate(['name' => 'Event Create', 'guard_name' => 'api', 'group_name' => 'Event Permissions']);
        Permission::firstOrCreate(['name' => 'Event Update', 'guard_name' => 'api', 'group_name' => 'Event Permissions']);
        Permission::firstOrCreate(['name' => 'Event Approve', 'guard_name' => 'api', 'group_name' => 'Event Permissions']);
        
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdminRole->syncPermissions(Permission::all());
    }

    public function test_normal_user_creating_event_is_forced_to_pending(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->givePermissionTo('Event Create');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/events', [
            'title' => 'Test Event Title',
            'status' => 'approved',
            'created_date' => '2026-05-26',
            'description' => 'Test Event Description'
        ]);

        $response->assertStatus(201);
        
        // Assert status was forced to pending
        $this->assertDatabaseHas('events', [
            'title' => 'Test Event Title',
            'status' => 'pending',
            'decision_by' => null,
            'decision_at' => null
        ]);
    }

    public function test_super_admin_creating_event_retains_approved_status(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/events', [
            'title' => 'Super Admin Event',
            'status' => 'approved',
            'created_date' => '2026-05-26',
            'description' => 'Super Admin Event Description'
        ]);

        $response->assertStatus(201);
        
        // Assert status is approved
        $this->assertDatabaseHas('events', [
            'title' => 'Super Admin Event',
            'status' => 'approved',
            'decision_by' => $user->id
        ]);
        $this->assertNotNull($response->json('data.decision_at'));
    }

    public function test_normal_user_cannot_update_status_to_approved(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->givePermissionTo(['Event Update']);

        $token = auth('api')->login($user);

        $event = Event::create([
            'title' => 'Initial Event',
            'slug' => 'initial-event',
            'status' => 'pending',
            'created_date' => '2026-05-26',
            'description' => 'Initial Description',
            'created_by' => $user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Updated Title',
            'status' => 'approved',
            'description' => 'Updated Description'
        ]);

        $response->assertStatus(403);
        
        // Assert title was also not updated since request aborted
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Initial Event',
            'status' => 'pending'
        ]);
    }

    public function test_super_admin_can_update_status_to_approved(): void
    {
        $user = User::factory()->create(['is_active' => true, 'can_login' => true]);
        $user->assignRole('Super Admin');

        $token = auth('api')->login($user);

        $event = Event::create([
            'title' => 'Initial Event',
            'slug' => 'initial-event',
            'status' => 'pending',
            'created_date' => '2026-05-26',
            'description' => 'Initial Description',
            'created_by' => $user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Updated Title',
            'status' => 'approved',
            'description' => 'Updated Description'
        ]);

        $response->assertStatus(200);
        
        // Assert event is updated
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
            'status' => 'approved',
            'decision_by' => $user->id
        ]);
    }
}
