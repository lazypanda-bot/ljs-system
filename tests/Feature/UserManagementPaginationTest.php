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

    public function test_user_tabs_use_text_only_shared_pill_controls(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertSee('staff-user-tabs', false)
            ->assertSee('staff-property-admin-header', false)
            ->assertSee('listings-toolbar', false)
            ->assertSee('usersSearchWrapper', false)
            ->assertSee('aria-label="Search staff accounts by name"', false)
            ->assertSee('All Users')
            ->assertSee('Teams')
            ->assertDontSee('fa-users me-2', false)
            ->assertDontSee('fa-layer-group me-2', false);

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'staff-user-tabs'), strpos($content, '<h2>Staff management</h2>'));
        $this->assertLessThan(strpos($content, 'staff-user-tabs'), strpos($content, 'id="openAddUserModal"'));
    }
}
