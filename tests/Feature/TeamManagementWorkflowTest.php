<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_edit_route_renders_the_edit_form_and_member_checkboxes(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leader = User::factory()->create(['role_name' => 'Broker']);
        $member = User::factory()->create(['role_name' => 'Agent']);
        $team = Team::create(['user_id' => $leader->user_id, 'team_name' => 'Coastal Team']);
        $team->members()->create([
            'user_id' => $member->user_id,
            'status' => 'Approved',
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.teams.edit', $team))
            ->assertOk()
            ->assertSee('Edit Team')
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Coastal Team')
            ->assertSee('Teams')
            ->assertSee('name="member_ids[]"', false)
            ->assertSee('value="' . $member->user_id . '" checked', false);
    }

    public function test_team_update_saves_multiple_selected_members_and_removes_unchecked_members(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leader = User::factory()->create(['role_name' => 'Broker']);
        $oldMember = User::factory()->create(['role_name' => 'Agent']);
        $newMember = User::factory()->create(['role_name' => 'Agent']);
        $team = Team::create(['user_id' => $leader->user_id, 'team_name' => 'Old Team']);
        $team->members()->create([
            'user_id' => $oldMember->user_id,
            'status' => 'Approved',
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)->put(route('admin.teams.update', $team), [
            'team_name' => 'Updated Team',
            'user_id' => $leader->user_id,
            'member_ids' => [$oldMember->user_id, $newMember->user_id],
        ])->assertRedirect(route('admin.teams.index'));

        $this->assertDatabaseHas('teams', ['team_id' => $team->team_id, 'team_name' => 'Updated Team']);
        $this->assertDatabaseHas('team_members', ['team_id' => $team->team_id, 'user_id' => $oldMember->user_id, 'status' => 'Approved']);
        $this->assertDatabaseHas('team_members', ['team_id' => $team->team_id, 'user_id' => $newMember->user_id, 'status' => 'Approved']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $newMember->user_id, 'type' => 'team']);

        $this->put(route('admin.teams.update', $team), [
            'team_name' => 'Updated Team',
            'user_id' => $leader->user_id,
            'member_ids' => [$newMember->user_id],
        ])->assertRedirect(route('admin.teams.index'));
        $this->assertDatabaseMissing('team_members', ['team_id' => $team->team_id, 'user_id' => $oldMember->user_id]);
    }

    public function test_team_creation_accepts_multiple_checkbox_selected_members(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leader = User::factory()->create(['role_name' => 'Broker']);
        $firstMember = User::factory()->create(['role_name' => 'Agent']);
        $secondMember = User::factory()->create(['role_name' => 'Agent']);

        $this->actingAs($admin)->get(route('admin.teams.index'))
            ->assertOk()
            ->assertSee('name="member_ids[]"', false)
            ->assertSee($firstMember->name)
            ->assertSee($secondMember->name);

        $this->post(route('admin.teams.store'), [
            'team_name' => 'New Coastal Team',
            'user_id' => $leader->user_id,
            'member_ids' => [$firstMember->user_id, $secondMember->user_id],
        ])->assertRedirect(route('admin.teams.index'));

        $team = Team::query()->where('team_name', 'New Coastal Team')->firstOrFail();
        $this->assertDatabaseHas('team_members', ['team_id' => $team->team_id, 'user_id' => $firstMember->user_id]);
        $this->assertDatabaseHas('team_members', ['team_id' => $team->team_id, 'user_id' => $secondMember->user_id]);
    }
}
