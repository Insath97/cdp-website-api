<?php

namespace Tests\Feature;

use App\Models\LeadershipMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLeadershipTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $overrides = []): LeadershipMember
    {
        return LeadershipMember::create(array_merge([
            'section' => 'executive',
            'name' => 'Test Member',
            'designation' => 'Test Designation',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_guest_can_list_all_active_leadership_members(): void
    {
        $this->createMember(['name' => 'Chairman Person', 'section' => 'chairman']);
        $this->createMember(['name' => 'CEO Person', 'section' => 'executive']);
        $this->createMember(['name' => 'Inactive Person', 'is_active' => false]);

        $response = $this->getJson('/api/v1/public/leadership');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.total', 2);
    }

    public function test_section_param_filters_by_chairman(): void
    {
        $this->createMember(['name' => 'Chairman Person', 'section' => 'chairman', 'sort_order' => 1]);
        $this->createMember(['name' => 'CEO Person', 'section' => 'executive']);
        $this->createMember(['name' => 'Inactive Chairman', 'section' => 'chairman', 'is_active' => false]);

        $response = $this->getJson('/api/v1/public/leadership?section=chairman');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.section', 'chairman');
        $response->assertJsonPath('data.data.0.name', 'Chairman Person');
    }

    public function test_section_param_filters_by_executive_and_specialised(): void
    {
        $this->createMember(['name' => 'Chairman Person', 'section' => 'chairman']);
        $this->createMember(['name' => 'CEO Person', 'section' => 'executive']);
        $this->createMember(['name' => 'Specialist Person', 'section' => 'specialised']);

        $executive = $this->getJson('/api/v1/public/leadership?section=executive');
        $executive->assertStatus(200);
        $executive->assertJsonPath('data.total', 1);
        $executive->assertJsonPath('data.data.0.section', 'executive');

        $specialised = $this->getJson('/api/v1/public/leadership?section=specialised');
        $specialised->assertStatus(200);
        $specialised->assertJsonPath('data.total', 1);
        $specialised->assertJsonPath('data.data.0.section', 'specialised');
    }

    public function test_empty_response_returns_empty_message(): void
    {
        $response = $this->getJson('/api/v1/public/leadership?section=chairman');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.total', 0);
        $this->assertEquals('No leadership members found.', $response->json('message'));
    }

    public function test_search_param_filters_members(): void
    {
        $this->createMember(['name' => 'CEO Person', 'designation' => 'Group Chief Executive Officer']);
        $this->createMember(['name' => 'Other Person', 'designation' => 'Country Manager']);

        $response = $this->getJson('/api/v1/public/leadership?search=CEO');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.data.0.name', 'CEO Person');
    }

    public function test_guest_can_view_single_leadership_member_with_qualifications(): void
    {
        $member = $this->createMember(['name' => 'Detailed Person']);
        $member->qualifications()->create(['title' => 'B.Sc. Honours', 'sort_order' => 0]);
        $member->qualifications()->create(['title' => 'MBA', 'sort_order' => 1]);

        $response = $this->getJson("/api/v1/public/leadership/{$member->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.name', 'Detailed Person');
        $response->assertJsonCount(2, 'data.qualifications');
    }

    public function test_show_returns_404_for_missing_or_inactive_member(): void
    {
        $inactive = $this->createMember(['name' => 'Inactive Person', 'is_active' => false]);

        $this->getJson('/api/v1/public/leadership/999999')->assertStatus(404);
        $this->getJson("/api/v1/public/leadership/{$inactive->id}")->assertStatus(404);
    }
}
