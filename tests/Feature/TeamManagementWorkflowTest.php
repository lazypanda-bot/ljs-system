<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationMessage;
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

        $response = $this->actingAs($admin)->get(route('admin.teams.edit', $team));

        $response
            ->assertOk()
            ->assertSee('Edit Team')
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Coastal Team')
            ->assertSee('Teams')
            ->assertSee('au-team-edit-open', false)
            ->assertSee('name="member_ids[]"', false)
            ->assertSee('value="' . $member->user_id . '" data-userid="' . $member->user_id . '" data-taken="false"', false);

        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="' . $member->user_id . '"[^>]*\bchecked\b/s',
            $response->getContent()
        );
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

    public function test_admin_can_change_team_status_without_changing_membership(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $leader = User::factory()->create(['role_name' => 'Broker']);
        $member = User::factory()->create(['role_name' => 'Agent']);
        $team = Team::create(['user_id' => $leader->user_id, 'team_name' => 'Status Team']);
        $teamMember = $team->members()->create([
            'user_id' => $member->user_id,
            'status' => 'Approved',
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)->put(route('admin.teams.update', $team), [
            'team_status' => 'Inactive',
        ])->assertRedirect(route('admin.teams.index'));

        $this->assertDatabaseHas('teams', ['team_id' => $team->team_id, 'team_status' => 'Inactive']);
        $this->assertDatabaseHas('team_members', [
            'member_id' => $teamMember->member_id,
            'team_id' => $team->team_id,
            'user_id' => $member->user_id,
        ]);
    }

    public function test_team_status_only_accepts_active_or_inactive_and_requires_admin_access(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $broker = User::factory()->create(['role_name' => 'Broker']);
        $leader = User::factory()->create(['role_name' => 'Broker']);
        $team = Team::create(['user_id' => $leader->user_id, 'team_name' => 'Protected Team']);

        $this->actingAs($admin)->put(route('admin.teams.update', $team), [
            'team_status' => 'Deleted',
        ])->assertSessionHasErrors('team_status');
        $this->assertDatabaseHas('teams', ['team_id' => $team->team_id, 'team_status' => 'Active']);

        $this->actingAs($broker)->put(route('admin.teams.update', $team), [
            'team_status' => 'Inactive',
        ])->assertForbidden();
        $this->assertDatabaseHas('teams', ['team_id' => $team->team_id, 'team_status' => 'Active']);
    }

    public function test_creating_a_team_transfers_members_from_inactive_teams_and_preserves_chat_history(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $oldLeader = User::factory()->create(['role_name' => 'Broker']);
        $newLeader = User::factory()->create(['role_name' => 'Broker']);
        $member = User::factory()->create(['role_name' => 'Agent']);
        $inactiveTeam = Team::create([
            'user_id' => $oldLeader->user_id,
            'team_name' => 'Archived Team',
            'team_status' => 'Inactive',
        ]);
        $inactiveTeam->members()->create([
            'user_id' => $member->user_id,
            'status' => 'Approved',
            'requested_at' => now(),
            'approved_at' => now(),
        ]);
        $oldConversation = Conversation::create([
            'type' => 'team',
            'team_id' => $inactiveTeam->team_id,
            'created_by' => $oldLeader->user_id,
        ]);
        $oldConversation->participants()->createMany([
            ['user_id' => $oldLeader->user_id],
            ['user_id' => $member->user_id],
        ]);
        $oldMessage = ConversationMessage::create([
            'conversation_id' => $oldConversation->conversation_id,
            'sender_id' => $oldLeader->user_id,
            'body' => 'Archived team history.',
        ]);

        $this->actingAs($admin)->get(route('admin.teams.index'))
            ->assertOk()
            ->assertSee($member->name)
            ->assertDontSee($member->name . ' - Already in team');

        $this->post(route('admin.teams.store'), [
            'team_name' => 'New Team',
            'user_id' => $newLeader->user_id,
            'member_ids' => [$member->user_id],
        ])->assertRedirect(route('admin.teams.index'));

        $newTeam = Team::query()->where('team_name', 'New Team')->firstOrFail();
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $inactiveTeam->team_id,
            'user_id' => $member->user_id,
        ]);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $newTeam->team_id,
            'user_id' => $member->user_id,
            'status' => 'Approved',
        ]);
        $this->assertDatabaseHas('conversation_messages', [
            'message_id' => $oldMessage->message_id,
            'body' => 'Archived team history.',
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $oldConversation->conversation_id,
            'user_id' => $member->user_id,
        ]);
    }

    public function test_editing_a_team_transfers_selected_members_from_inactive_teams(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $archivedLeader = User::factory()->create(['role_name' => 'Broker']);
        $activeLeader = User::factory()->create(['role_name' => 'Broker']);
        $member = User::factory()->create(['role_name' => 'Agent']);
        $inactiveTeam = Team::create([
            'user_id' => $archivedLeader->user_id,
            'team_name' => 'Archived Team',
            'team_status' => 'Inactive',
        ]);
        $inactiveTeam->members()->create([
            'user_id' => $member->user_id,
            'status' => 'Approved',
        ]);
        $activeTeam = Team::create(['user_id' => $activeLeader->user_id, 'team_name' => 'Active Team']);

        $this->actingAs($admin)->put(route('admin.teams.update', $activeTeam), [
            'team_name' => 'Active Team',
            'user_id' => $activeLeader->user_id,
            'member_ids' => [$member->user_id],
        ])->assertRedirect(route('admin.teams.index'));

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $inactiveTeam->team_id,
            'user_id' => $member->user_id,
        ]);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $activeTeam->team_id,
            'user_id' => $member->user_id,
            'status' => 'Approved',
        ]);
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
