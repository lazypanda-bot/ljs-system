<?php

namespace Tests\Feature;

use App\Mail\ActivityMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAndQueueWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_pages_show_complete_company_content(): void
    {
        $this->get(route('about'))->assertOk()
            ->assertSee('LJS Realty and Brokerage')
            ->assertSee('Find a property')
            ->assertSee('href="mailto:ljsrealty@gmail.com"', false)
            ->assertSee('href="tel:+639213456789"', false)
            ->assertDontSee('id="openContactUsDialog"', false)
            ->assertDontSee('Contact options')
            ->assertDontSee('Lorem ipsum')
            ->assertDontSee('1,200+ Properties Sold')
            ->assertDontSee('Established in 2012');

        $this->get(route('contact'))->assertOk()
            ->assertSee('Send a message')
            ->assertSee('Unit 6 Labangon Town Center')
            ->assertSee(route('contact.submit'));
    }

    public function test_contact_form_queues_message_to_configured_contact_inbox(): void
    {
        Mail::fake();
        config(['services.contact_email' => 'office@example.test']);

        $this->from(route('contact'))->post(route('contact.submit'), [
            'name' => 'Taylor Client',
            'email' => 'taylor@example.test',
            'phone' => '555-0101',
            'message' => 'I would like to ask about a property.',
        ])->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        Mail::assertQueued(ActivityMail::class, fn (ActivityMail $mail) =>
            $mail->subjectLine === 'Website inquiry from Taylor Client'
            && $mail->replyToEmail === 'taylor@example.test'
        );
    }
}