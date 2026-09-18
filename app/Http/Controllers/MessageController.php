<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\AppNotification;
use App\Models\Property;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        $this->ensureTeamConversations($user);
        AppNotification::where('user_id', $user->user_id)
            ->where('type', 'message')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->user_id))
            ->with(['participants.user', 'team', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->latest('updated_at')
            ->get();

        $selected = $request->integer('conversation');
        $conversation = $selected ? $conversations->firstWhere('conversation_id', $selected) : $conversations->first();

        if ($conversation) {
            $conversation->unsetRelation('messages');
            $conversation->load(['messages.sender', 'participants.user']);
            $conversation->participants()->where('user_id', $user->user_id)->update(['last_read_at' => now()]);
        }

        $contacts = $this->availableContacts($user);
        $isClient = $user->normalizedRole() === 'client';

        return view('pages.messages', compact('conversations', 'conversation', 'contacts', 'isClient'));
    }

    public function direct(User $recipient)
    {
        $user = $this->user();
        abort_unless($this->canMessage($user, $recipient), 403);

        $conversation = DB::transaction(function () use ($user, $recipient) {
            $conversation = Conversation::query()
                ->where('type', 'direct')
                ->whereHas('participants', fn ($query) => $query->where('user_id', $user->user_id))
                ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->user_id))
                ->withCount('participants')
                ->get()
                ->firstWhere('participants_count', 2);

            if ($conversation) {
                return $conversation;
            }

            $conversation = Conversation::create(['type' => 'direct', 'created_by' => $user->user_id]);
            $conversation->participants()->createMany([
                ['user_id' => $user->user_id],
                ['user_id' => $recipient->user_id],
            ]);

            return $conversation;
        });

        return redirect()->route('messages.inbox', ['conversation' => $conversation->conversation_id]);
    }

    public function compose(Request $request)
    {
        $user = $this->user();
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,user_id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
        $recipient = User::findOrFail($validated['recipient_id']);
        abort_unless($this->canMessage($user, $recipient), 403);

        $conversation = $this->findOrCreateDirectConversation($user, $recipient);
        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('message-attachments', 'public')
            : null;

        $this->createMessage($conversation, $user, $validated['body'], $validated['subject'] ?? null, $attachmentPath);

        return redirect()->route('messages.inbox', ['conversation' => $conversation->conversation_id]);
    }

    public function propertyDirect(Property $property)
    {
        $user = $this->user();
        $recipient = $property->user;

        abort_unless(
            $user->normalizedRole() === 'client'
                && $recipient instanceof User
                && $recipient->account_status === 'Active'
                && in_array($recipient->role_name, ['Broker', 'Agent'], true),
            403
        );

        $conversation = $this->findOrCreateDirectConversation($user, $recipient);

        if (! $conversation->messages()->exists()) {
            $body = "Interested in this property:\n{$property->property_name}\nPHP "
                . number_format($property->price, 0)
                . "\n" . route('properties.show', $property);
            $this->createMessage($conversation, $user, $body, 'Property enquiry: ' . $property->property_name);
        }

        return redirect()->route('messages.inbox', ['conversation' => $conversation->conversation_id]);
    }

    private function findOrCreateDirectConversation(User $user, User $recipient): Conversation
    {
        return DB::transaction(function () use ($user, $recipient) {
            $conversation = Conversation::query()
                ->where('type', 'direct')
                ->whereHas('participants', fn ($query) => $query->where('user_id', $user->user_id))
                ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->user_id))
                ->withCount('participants')
                ->get()
                ->firstWhere('participants_count', 2);

            if ($conversation) {
                return $conversation;
            }

            $conversation = Conversation::create(['type' => 'direct', 'created_by' => $user->user_id]);
            $conversation->participants()->createMany([
                ['user_id' => $user->user_id],
                ['user_id' => $recipient->user_id],
            ]);

            return $conversation;
        });
    }

    public function store(Request $request, Conversation $conversation)
    {
        $user = $this->user();
        abort_unless($this->participates($conversation, $user), 403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $this->createMessage($conversation, $user, $validated['body']);

        return redirect()->route('messages.inbox', ['conversation' => $conversation->conversation_id]);
    }

    public function updates(Request $request)
    {
        $user = $this->user();
        $conversationId = $request->integer('conversation');
        $latestMessageId = $request->integer('after');

        $conversation = Conversation::findOrFail($conversationId);
        abort_unless($this->participates($conversation, $user), 403);

        $messages = $conversation->messages()
            ->with('sender:user_id,name,role_name')
            ->where('message_id', '>', $latestMessageId)
            ->orderBy('message_id')
            ->get()
            ->map(fn ($message) => [
                'id' => $message->message_id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'sender_role' => $message->sender->normalizedRole(),
                'sent_at' => $message->created_at->format('M d, g:i A'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function notificationCount()
    {
        $user = $this->user();

        return response()->json([
            'count' => AppNotification::where('user_id', $user->user_id)->whereNull('read_at')->count(),
        ]);
    }

    public function openNotification(AppNotification $notification)
    {
        $user = $this->user();
        abort_unless($notification->user_id === $user->user_id, 403);

        $notification->update(['read_at' => now()]);

        return redirect($notification->url ?: route('messages.inbox'));
    }

    private function ensureTeamConversations(User $user): void
    {
        $teams = Team::query()
            ->where('user_id', $user->user_id)
            ->orWhereHas('members', fn ($query) => $query->where('user_id', $user->user_id)->where('status', 'Approved'))
            ->with(['members' => fn ($query) => $query->where('status', 'Approved')])
            ->get();

        foreach ($teams as $team) {
            $conversation = Conversation::firstOrCreate(
                ['type' => 'team', 'team_id' => $team->team_id],
                ['created_by' => $team->user_id]
            );
            $participantIds = $team->members->pluck('user_id')->push($team->user_id)->unique();
            foreach ($participantIds as $participantId) {
                $conversation->participants()->firstOrCreate(['user_id' => $participantId]);
            }
        }
    }

    private function createMessage(Conversation $conversation, User $sender, string $body, ?string $subject = null, ?string $attachmentPath = null): void
    {
        ConversationMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'subject' => $subject,
            'body' => $body,
            'attachment_path' => $attachmentPath,
        ]);
        $conversation->touch();

        $conversation->participants()->where('user_id', '!=', $sender->user_id)->pluck('user_id')->each(function ($recipientId) use ($conversation, $sender, $body) {
            AppNotification::create(['user_id' => $recipientId, 'type' => 'message', 'title' => 'New message from ' . $sender->name, 'body' => str($body)->limit(120), 'url' => route('messages.inbox', ['conversation' => $conversation->conversation_id])]);
        });
    }

    private function availableContacts(User $user)
    {
        return User::query()
            ->where('account_status', 'Active')
            ->whereKeyNot($user->user_id)
            ->when($user->normalizedRole() === 'client', fn ($query) => $query->whereIn('role_name', ['Broker', 'Agent']))
            ->orderBy('name')
            ->get(['user_id', 'name', 'role_name']);
    }

    private function canMessage(User $sender, User $recipient): bool
    {
        if ($recipient->account_status !== 'Active' || $sender->user_id === $recipient->user_id) {
            return false;
        }

        return $sender->normalizedRole() !== 'client'
            || in_array($recipient->role_name, ['Broker', 'Agent'], true);
    }

    private function participates(Conversation $conversation, User $user): bool
    {
        return $conversation->participants()->where('user_id', $user->user_id)->exists();
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}