<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\PropertyAssign;
use App\Models\PropertyInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_filters_events_by_range_and_listing_owner(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $ownerOne = User::factory()->create(['role_name' => 'Admin']);
        $ownerTwo = User::factory()->create(['role_name' => 'Admin']);
        $client = User::factory()->create(['role_name' => 'Client']);
        $periodStart = now()->subDays(4)->startOfDay();
        $propertyOne = $this->propertyFor($ownerOne, 'One', $periodStart);
        $propertyTwo = $this->propertyFor($ownerTwo, 'Two', $periodStart);
        $assignmentOne = PropertyAssign::create(['user_id' => $ownerOne->user_id, 'property_id' => $propertyOne->property_id, 'assigned_date' => now()->toDateString(), 'status' => 'Approved']);
        $appointment = Appointment::create([
            'assign_id' => $assignmentOne->assign_id,
            'contact_name' => 'Client One',
            'contact_number' => '555-0100',
            'contact_email' => 'client-one@example.test',
            'appointment_type' => 'Viewing',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
            'appointment_status' => 'Completed',
            'completed_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
        ]);
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $client->user_id]);
        $inquiry = PropertyInquiry::create([
            'property_id' => $propertyOne->property_id,
            'conversation_id' => $conversation->conversation_id,
            'client_id' => $client->user_id,
            'recipient_id' => $ownerOne->user_id,
        ]);
        $inquiry->forceFill(['created_at' => now()->subDay()])->save();
        $propertyOne->update(['property_status' => 'Sold', 'sold_at' => now()->subDay()]);

        $query = [
            'page' => 'report',
            'from' => $periodStart->toDateString(),
            'to' => now()->toDateString(),
            'user_id' => $ownerOne->user_id,
        ];

        $this->actingAs($admin)->get(route('app.page', $query))->assertOk()
            ->assertSee('data-report-value="properties">1', false)
            ->assertSee('data-report-value="inquiries">1', false)
            ->assertSee('data-report-value="appointments">1', false)
            ->assertSee('data-report-value="completed">1', false)
            ->assertSee('data-report-value="sold">1', false)
            ->assertSee($ownerOne->name)
            ->assertSee($ownerTwo->name)
            ->assertDontSee('data-staff-row="' . $ownerTwo->user_id . '"', false);

        $todayReport = $this->get(route('app.page', [...$query, 'trend_date' => today()->toDateString()]));
        $todayReport->assertOk()->assertSee('name="trend_date" value="' . today()->toDateString() . '"', false);
        $this->assertSame(1, substr_count($todayReport->getContent(), 'class="report-trend-row"'));

        $selectedDayReport = $this->get(route('app.page', [...$query, 'trend_date' => now()->subDay()->toDateString()]));
        $selectedDayReport->assertOk()->assertSee('1 inquiries, 0 appointments, 1 completed, 1 sold');

        $csv = $this->get(route('reports.export', $query));
        $csv->assertOk()->assertDownload('ljs-property-report.csv');
        $this->assertStringContainsString('"Property inquiries",1', $csv->streamedContent());
        $this->assertStringContainsString($ownerOne->name, $csv->streamedContent());
    }

    public function test_report_repair_migration_restores_a_missing_inquiry_table(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('property_inquiries');

        $migration = require database_path('migrations/2026_09_30_120000_repair_reporting_queue_schema.php');
        $migration->up();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('property_inquiries'));
    }

    private function propertyFor(User $owner, string $suffix, $createdAt): Property
    {
        return Property::create([
            'user_id' => $owner->user_id,
            'property_name' => 'Property ' . $suffix,
            'property_description' => 'Report test listing.',
            'property_type' => 'House & Lot',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'admin',
            'created_at' => $createdAt,
        ]);
    }
}