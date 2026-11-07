<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadBrokerStatusProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_activate_or_deactivate_a_lead_broker_using_a_direct_request(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $lead = User::factory()->create(['role_name' => 'Lead Broker', 'account_status' => 'Active']);
        $this->actingAs($admin)->put(route('admin.users.update', $lead), ['account_status' => 'Inactive'])->assertForbidden();
        $this->assertSame('Active', $lead->fresh()->account_status);

        $lead->update(['account_status' => 'Inactive']);
        $this->put(route('admin.users.update', $lead), ['account_status' => 'Active'])->assertForbidden();
        $this->assertSame('Inactive', $lead->fresh()->account_status);
    }

    public function test_lead_broker_status_is_read_only_for_admin_in_full_and_paginated_tables(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $lead = User::factory()->create(['role_name' => 'Lead Broker', 'name' => 'Protected Lead', 'account_status' => 'Inactive']);
        $this->actingAs($admin);
        foreach ([false, true] as $partial) {
            $response = $partial
                ? $this->getJson(route('admin.users.index', ['table_only' => 1]))
                : $this->get(route('admin.users.index'));
            $response->assertOk();
            $html = $partial ? $response->json('html') : $response->getContent();
            $document = new \DOMDocument;
            $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($document);
            $row = $xpath->query('//tr[td//span[normalize-space(.)="Protected Lead"]]')->item(0);
            $this->assertNotNull($row);
            $this->assertSame(0, $xpath->query('.//select[@name="account_status"]', $row)->length);
            $this->assertStringContainsString('Inactive', $row->textContent);
        }
    }

    public function test_admin_can_still_change_agent_and_broker_status(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $this->actingAs($admin);
        foreach (['Agent', 'Broker'] as $role) {
            $professional = User::factory()->create(['role_name' => $role, 'account_status' => 'Active']);
            $this->put(route('admin.users.update', $professional), ['account_status' => 'Inactive'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('Inactive', $professional->fresh()->account_status);
        }
    }

    public function test_lead_broker_in_admin_mode_can_manage_another_lead_broker_but_not_themselves(): void
    {
        $lead = User::factory()->create(['role_name' => 'Lead Broker']);
        $other = User::factory()->create(['role_name' => 'Lead Broker', 'account_status' => 'Active']);
        $this->actingAs($lead)->withSession(['acting_as' => 'admin'])
            ->put(route('admin.users.update', $other), ['account_status' => 'Inactive'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Inactive', $other->fresh()->account_status);
        $this->put(route('admin.users.update', $lead), ['account_status' => 'Inactive'])->assertSessionHasErrors('account_status');
        $this->assertSame('Active', $lead->fresh()->account_status);
    }
}
