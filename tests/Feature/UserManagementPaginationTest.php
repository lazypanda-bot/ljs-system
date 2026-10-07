<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_list_shows_seven_staff_users_per_page(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        User::factory()->count(8)->create(['role_name' => 'Agent']);

        $firstPage = $this->actingAs($admin)->get(route('admin.users.index'));
        $firstPage->assertOk();
        $this->assertSame(7, substr_count($firstPage->getContent(), 'class="au-user-cell"'));

        $secondPage = $this->get(route('admin.users.index', ['page' => 2]));
        $secondPage->assertOk();
        $this->assertSame(2, substr_count($secondPage->getContent(), 'class="au-user-cell"'));
    }

    public function test_ajax_pagination_returns_only_the_requested_users_table_page(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin', 'name' => 'First Page Admin']);
        User::factory()->count(8)->create(['role_name' => 'Agent']);

        $response = $this->actingAs($admin)->getJson(route('admin.users.index', [
            'page' => 2,
            'table_only' => 1,
        ]))->assertOk()->assertJsonStructure(['html']);

        $html = $response->json('html');
        $this->assertSame(2, substr_count($html, 'class="au-user-cell"'));
        $this->assertStringContainsString('id="usersTableContent"', $html);
        $this->assertStringContainsString('class="au-pagination"', $html);
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringNotContainsString('table_only=1', $html);
        $this->assertStringNotContainsString('Staff management', $html);
    }

    public function test_credential_action_uses_the_shared_users_table_style(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $agent = User::factory()->create(['role_name' => 'Agent']);
        $agent->professionalProfile()->create(['document_file' => 'professional-credentials/license.pdf']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('class="au-view-credential-btn"', false)
            ->assertSee('View credential');
    }

    public function test_user_tabs_use_text_only_shared_pill_controls(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertSee('staff-user-tabs', false)
            ->assertSee('staff-property-admin-header', false)
            ->assertSee('listings-toolbar', false)
            ->assertSee('usersSearchWrapper', false)
            ->assertSee('aria-label="Search users or teams"', false)
            ->assertSee('All Users')
            ->assertSee('Teams')
            ->assertDontSee('fa-users me-2', false)
            ->assertDontSee('fa-layer-group me-2', false);

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'staff-user-tabs'), strpos($content, '<h2>Staff management</h2>'));
        $this->assertLessThan(strpos($content, 'id="openAddUserModal"'), strpos($content, 'id="searchUsers"'));
        $this->assertLessThan(strpos($content, 'id="openAddTeamModal"'), strpos($content, 'id="openAddUserModal"'));
    }
}
