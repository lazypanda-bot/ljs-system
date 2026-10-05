<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ConversationParticipant;
use App\Models\AppNotification;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingSearchAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_search_and_unread_filter_only_return_matching_participant_threads(): void
    {
        [$client, $other, $outsider] = [
            User::factory()->create(['role_name' => 'Client']),
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Broker']),
        ];
        $unread = $this->conversation($client, $other, 'needle property question', 'Subject needle');
        $read = $this->conversation($client, User::factory()->create(['role_name' => 'Broker']), 'other topic', null, $client->user_id);
        $this->conversation($outsider, User::factory()->create(['role_name' => 'Agent']), 'needle secret thread', null);
        $property = Property::create([
            'user_id' => $other->user_id,
            'property_name' => 'Harbor Point Residence',
            'property_description' => 'Property search test.',
            'property_type' => 'Condominium',
            'street' => '2 Waterfront Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 4500000,
            'property_status' => 'For Sale',
            'approval_status' => 'Approved',
            'listing_mode' => 'professional',
        ]);
        PropertyInquiry::create([
            'property_id' => $property->property_id,
            'conversation_id' => $unread->conversation_id,
            'client_id' => $client->user_id,
            'recipient_id' => $other->user_id,
        ]);

        $unreadResponse = $this->actingAs($client)->get(route('messages.inbox', ['status' => 'unread']));
        $unreadResponse->assertOk()->assertSee('needle property question')->assertDontSee('other topic');

        $search = $this->get(route('messages.inbox', ['q' => 'needle']));
        $search->assertOk()->assertSee('needle property question')->assertDontSee('other topic')->assertDontSee('needle secret thread');
        $this->get(route('messages.inbox', ['q' => 'Harbor Point']))
            ->assertOk()
            ->assertSee('needle property question');
        $this->assertSame($unread->conversation_id, $unread->fresh()->conversation_id);
        $this->assertNotSame($read->conversation_id, $unread->conversation_id);
    }

    public function test_attachment_download_requires_conversation_participation_and_deletion_cleans_file(): void
    {
        Storage::fake('local');
        [$sender, $recipient, $outsider] = [
            User::factory()->create(['role_name' => 'Client']),
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Broker']),
        ];
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $sender->user_id]);
        $conversation->participants()->createMany([
            ['user_id' => $sender->user_id],
            ['user_id' => $recipient->user_id],
        ]);
        $path = UploadedFile::fake()->create('private-note.txt', 1)->store('message-attachments', 'local');
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'body' => 'See the attachment.',
            'attachment_path' => $path,
        ]);

        $this->actingAs($outsider)->get(route('messages.attachments.download', $message))->assertForbidden();
        $this->actingAs($recipient)->get(route('messages.attachments.download', $message))->assertOk();

        $message->delete();
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_users_can_delete_only_their_own_messages(): void
    {
        Storage::fake('local');
        [$sender, $recipient, $outsider] = [
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Client']),
            User::factory()->create(['role_name' => 'Broker']),
        ];
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $sender->user_id]);
        $conversation->participants()->createMany([
            ['user_id' => $sender->user_id],
            ['user_id' => $recipient->user_id],
        ]);
        $path = UploadedFile::fake()->create('owned-message.txt', 1)->store('message-attachments', 'local');
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'body' => 'Message to remove.',
            'attachment_path' => $path,
        ]);

        $this->actingAs($recipient)->delete(route('messages.destroy', $message))->assertForbidden();
        $this->assertTrue(Storage::disk('local')->exists($path));

        $this->actingAs($sender)->delete(route('messages.destroy', ['message' => $message, 'panel' => 1]))
            ->assertRedirect(route('messages.inbox', ['conversation' => $conversation->conversation_id, 'panel' => 1]));
        $this->assertDatabaseMissing('conversation_messages', ['message_id' => $message->message_id]);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_users_can_edit_only_their_own_messages(): void
    {
        [$sender, $recipient] = [
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Client']),
        ];
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $sender->user_id]);
        $conversation->participants()->createMany([
            ['user_id' => $sender->user_id],
            ['user_id' => $recipient->user_id],
        ]);
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'body' => 'Original message.',
        ]);

        $this->actingAs($recipient)->patch(route('messages.update', $message), ['body' => 'Unauthorized edit'])->assertForbidden();
        $this->actingAs($sender)->patch(route('messages.update', ['message' => $message, 'panel' => 1]), ['body' => 'Updated message'])
            ->assertRedirect(route('messages.inbox', ['conversation' => $conversation->conversation_id, 'panel' => 1]));

        $this->assertDatabaseHas('conversation_messages', ['message_id' => $message->message_id, 'body' => 'Updated message']);
    }

    public function test_only_own_messages_render_action_menu_and_composer_uses_send_button(): void
    {
        [$sender, $recipient] = [
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Client']),
        ];
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $sender->user_id]);
        $conversation->participants()->createMany([
            ['user_id' => $sender->user_id],
            ['user_id' => $recipient->user_id],
        ]);
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'body' => 'My message.',
        ]);
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $recipient->user_id,
            'body' => 'Their message.',
        ]);

        $response = $this->actingAs($sender)->get(route('messages.inbox', ['conversation' => $conversation->conversation_id]));
        $response->assertOk()
            ->assertSee('My message.')
            ->assertSee('Their message.')
            ->assertSee('aria-label="Message actions"', false)
            ->assertSee('data-edit-message', false)
            ->assertSee('>Send</span>', false)
            ->assertDontSee('fa-paper-plane', false)
            ->assertDontSee('fa-trash', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-message-menu-toggle'));
    }

    public function test_staff_messages_page_still_renders_the_notification_menu(): void
    {
        $user = User::factory()->create(['role_name' => 'Agent']);
        AppNotification::create([
            'user_id' => $user->user_id,
            'type' => 'team',
            'title' => 'Team notification',
            'body' => 'A team update.',
        ]);

        $this->actingAs($user)->get(route('messages.inbox'))
            ->assertOk()
            ->assertSee('notificationMenuButton')
            ->assertSee('notificationMenu')
            ->assertSee('Team notification');
    }

    public function test_removing_conversation_hides_it_only_for_current_user_until_new_message(): void
    {
        [$firstUser, $secondUser] = [
            User::factory()->create(['role_name' => 'Agent']),
            User::factory()->create(['role_name' => 'Client']),
        ];
        $conversation = $this->conversation($firstUser, $secondUser, 'Keep this thread for the other member.');
        $this->actingAs($firstUser)->get(route('messages.inbox'))
            ->assertOk()
            ->assertSee('data-thread-menu-toggle', false)
            ->assertSee('Delete');

        $this->actingAs($firstUser)->delete(route('messages.conversations.delete', $conversation))
            ->assertRedirect(route('messages.inbox'));
        $this->get(route('messages.inbox'))->assertDontSee('Keep this thread for the other member.');
        $this->actingAs($secondUser)->get(route('messages.inbox'))->assertSee('Keep this thread for the other member.');

        $this->actingAs($secondUser)->post(route('messages.store', $conversation), ['body' => 'A new message restores it.'])
            ->assertRedirect();
        $this->actingAs($firstUser)->get(route('messages.inbox'))->assertSee('A new message restores it.');
    }

    public function test_deleting_conversation_cleans_all_attached_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role_name' => 'Agent']);
        $path = UploadedFile::fake()->create('conversation-file.txt', 1)->store('message-attachments', 'local');
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $user->user_id]);
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $user->user_id,
            'body' => 'File lifecycle check.',
            'attachment_path' => $path,
        ]);

        $conversation->delete();

        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_existing_public_attachments_can_be_migrated_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $user = User::factory()->create(['role_name' => 'Agent']);
        $path = UploadedFile::fake()->create('legacy-attachment.txt', 1)->store('message-attachments', 'public');
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $user->user_id]);
        $conversation->participants()->create(['user_id' => $user->user_id]);
        $message = ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $user->user_id,
            'body' => 'Existing public file.',
            'attachment_path' => $path,
        ]);

        Artisan::call('messages:secure-attachments');

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->actingAs($user)->get(route('messages.attachments.download', $message))->assertOk();
    }

    private function conversation(User $first, User $second, string $body, ?string $subject = null, ?int $readBy = null): Conversation
    {
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $first->user_id]);
        $conversation->participants()->createMany([
            ['user_id' => $first->user_id, 'last_read_at' => $readBy === $first->user_id ? now() : null],
            ['user_id' => $second->user_id],
        ]);
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $second->user_id,
            'subject' => $subject,
            'body' => $body,
        ]);

        return $conversation;
    }
}