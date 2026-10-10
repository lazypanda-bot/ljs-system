<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\AppNotification;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertyAssign;
use App\Models\ReferralCode;
use App\Models\Team;
use App\Models\User;
use App\Mail\ActivityMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->user();
        $panelMode = $request->boolean('panel');
        abort_unless(! $panelMode || $user->normalizedRole() === 'client', 403);
        $this->ensureTeamConversations($user);
        AppNotification::visibleTo($user)
            ->where('type', 'message')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->user_id)->whereNull('hidden_at'))
            ->with(['participants.user', 'team', 'propertyInquiries.property', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . addcslashes($request->string('q')->toString(), '%_\\') . '%';
                $query->where(function ($query) use ($term) {
                    $query->whereHas('messages', fn ($messages) => $messages->where('body', 'like', $term)->orWhere('subject', 'like', $term))
                        ->orWhereHas('participants.user', fn ($participants) => $participants->where('name', 'like', $term))
                        ->orWhereHas('propertyInquiries.property', fn ($properties) => $properties->where('property_name', 'like', $term));
                });
            })
            ->when(in_array($request->query('type'), ['direct', 'team'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('status') === 'unread', fn ($query) => $query->whereHas('messages', function ($messages) use ($user) {
                $messages->where('sender_id', '!=', $user->user_id)
                    ->whereRaw('conversation_messages.created_at > COALESCE((SELECT last_read_at FROM conversation_participants WHERE conversation_participants.conversation_id = conversation_messages.conversation_id AND conversation_participants.user_id = ?), ?)', [$user->user_id, '1970-01-01 00:00:00']);
            }))
            ->latest('updated_at')
            ->get();

        $selected = $request->integer('conversation');
        $conversation = $selected
            ? $conversations->firstWhere('conversation_id', $selected)
            : ($panelMode ? null : $conversations->first());

        if ($conversation) {
            $conversation->unsetRelation('messages');
            $conversation->load(['messages.sender', 'participants.user']);
            $conversation->participants()->where('user_id', $user->user_id)->update(['last_read_at' => now()]);
        }

        $contacts = $this->availableContacts($user);
        $isClient = $user->normalizedRole() === 'client';

        $view = $panelMode ? 'pages.messages-panel' : 'pages.messages';

        return view($view, compact('conversations', 'conversation', 'contacts', 'isClient', 'panelMode'));
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
            ? $request->file('attachment')->store('message-attachments', 'local')
            : null;

        $this->createMessage($conversation, $user, $validated['body'], $validated['subject'] ?? null, $attachmentPath);

        return redirect()->route('messages.inbox', [
            'conversation' => $conversation->conversation_id,
            'panel' => $request->boolean('panel') ? 1 : null,
        ]);
    }

    public function propertyDirect(Request $request, Property $property)
    {
        $user = $this->user();
        // Messages about a listing always go to the person who listed it.
        $recipient = $property->user;

        abort_unless(
            $property->approval_status === 'Approved'
                && $user->normalizedRole() === 'client'
                && $recipient instanceof User
                && $recipient->account_status === 'Active',
            403
        );

        $conversation = $this->findOrCreateDirectConversation($user, $recipient);
        PropertyInquiry::firstOrCreate([
            'property_id' => $property->property_id,
            'conversation_id' => $conversation->conversation_id,
        ], [
            'client_id' => $user->user_id,
            'recipient_id' => $recipient->user_id,
        ]);

        if (! $conversation->messages()->exists()) {
            $body = "Interested in this property:\n{$property->property_name}\nPHP "
                . number_format($property->price, 0)
                . "\n" . route('properties.show', $property);
            $this->createMessage($conversation, $user, $body, 'Property enquiry: ' . $property->property_name);
        }

        $destination = route('messages.inbox', [
            'conversation' => $conversation->conversation_id,
            'panel' => $request->boolean('panel') ? 1 : null,
        ]);

        return $request->expectsJson() ? response()->json(['url' => $destination]) : redirect($destination);
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
                $conversation->participants()
                    ->whereIn('user_id', [$user->user_id, $recipient->user_id])
                    ->update(['hidden_at' => null]);

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

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('message-attachments', 'local')
            : null;

        $this->createMessage($conversation, $user, $validated['body'], null, $attachmentPath);

        return redirect()->route('messages.inbox', [
            'conversation' => $conversation->conversation_id,
            'panel' => $request->boolean('panel') ? 1 : null,
        ]);
    }

    public function deleteConversation(Request $request, Conversation $conversation)
    {
        $user = $this->user();
        $participant = $conversation->participants()->where('user_id', $user->user_id)->first();
        abort_unless($participant, 403);

        $participant->update(['hidden_at' => now()]);

        return redirect()->route('messages.inbox', [
            'panel' => $request->boolean('panel') ? 1 : null,
        ])->with('success', 'Conversation removed from your messages.');
    }

    public function destroyMessage(Request $request, ConversationMessage $message)
    {
        $user = $this->user();
        $conversation = Conversation::findOrFail($message->conversation_id);
        abort_unless($this->participates($conversation, $user), 403);
        abort_unless((int) $message->sender_id === (int) $user->user_id, 403);

        $message->delete();
        $conversation->touch();

        return redirect()->route('messages.inbox', [
            'conversation' => $conversation->conversation_id,
            'panel' => $request->boolean('panel') ? 1 : null,
        ])->with('success', 'Message deleted.');
    }

    public function updateMessage(Request $request, ConversationMessage $message)
    {
        $user = $this->user();
        $conversation = Conversation::findOrFail($message->conversation_id);
        abort_unless($this->participates($conversation, $user), 403);
        abort_unless((int) $message->sender_id === (int) $user->user_id, 403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $message->update(['body' => $validated['body']]);
        $conversation->touch();

        return redirect()->route('messages.inbox', [
            'conversation' => $conversation->conversation_id,
            'panel' => $request->boolean('panel') ? 1 : null,
        ])->with('success', 'Message updated.');
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
                'attachment_url' => $message->attachment_path ? route('messages.attachments.download', $message) : null,
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function downloadAttachment(ConversationMessage $message)
    {
        $user = $this->user();
        $conversation = Conversation::findOrFail($message->conversation_id);
        abort_unless($this->participates($conversation, $user), 403);

        $path = $message->attachment_path;
        abort_unless($path && str_starts_with($path, 'message-attachments/'), 404);
        $absolutePath = Storage::disk('local')->path($path);
        abort_unless(is_file($absolutePath), 404);

        $filename = str_replace('"', '', basename($path));

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function notificationCount(Request $request)
    {
        $user = $this->user();
        $notifications = AppNotification::visibleTo($user)
            ->whereNull('read_at');

        if ($user->normalizedRole() === 'client') {
            $notifications->when($request->query('type') === 'message', fn ($query) => $query->where('type', 'message'))
                ->when($request->query('type') === 'other', fn ($query) => $query->where('type', '!=', 'message'));
        }

        return response()->json([
            'count' => $notifications->count(),
        ]);
    }

    public function notifications(Request $request)
    {
        $user = $this->user();
        $filter = $request->query('filter', 'all');
        $relations = [
            'appointment',
            'appointment.assignment.property',
            'appointment.assignment.user',
            'appointment.client',
            'appointmentProposal.appointment.assignment.property',
            'appointmentProposal.appointment.assignment.user',
            'appointmentProposal.appointment.client',
            'appointmentProposal.proposedBy',
        ];

        $appointmentScope = fn ($query) => $query
            ->whereNotNull('appointment_id')
            ->orWhereNotNull('appointment_proposal_id');
        $countNotifications = function (?callable $scope = null) use ($user): int {
            $query = AppNotification::visibleTo($user);
            if ($scope) {
                $scope($query);
            }

            return $query->count();
        };
        $notificationSummary = [
            'all' => $countNotifications(),
            'unread' => $countNotifications(fn ($query) => $query->whereNull('read_at')),
            'appointments' => $countNotifications(fn ($query) => $query->where($appointmentScope)),
            'messages' => $countNotifications(fn ($query) => $query->where('type', 'message')),
            'team' => $countNotifications(fn ($query) => $query->where('type', 'team')),
        ];

        $notifications = AppNotification::visibleTo($user)
            ->with($relations)
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filter === 'appointments', fn ($query) => $query->where($appointmentScope))
            ->when($filter === 'messages', fn ($query) => $query->where('type', 'message'))
            ->when($filter === 'team', fn ($query) => $query->where('type', 'team'))
            ->latest()
            ->orderByDesc('notification_id')
            ->paginate(5)
            ->appends($request->except(['list_only', 'page']));

        if ($request->boolean('list_only')) {
            return response()->json([
                'html' => view('pages.partials.notification-list', [
                    'notifications' => $notifications,
                    'selectedNotification' => null,
                ])->render(),
            ]);
        }

        $selectedId = request()->integer('selected');
        $selectedNotification = $selectedId
            ? AppNotification::visibleTo($user)->with($relations)->whereKey($selectedId)->first()
            : null;

        if ($selectedNotification && ! $selectedNotification->read_at) {
            $selectedNotification->update(['read_at' => now()]);
            $notifications->getCollection()->firstWhere('notification_id', $selectedNotification->notification_id)?->setAttribute('read_at', now());
        }

        $selectedNotification ??= $notifications->getCollection()->first();

        return view('pages.notifications', compact('notifications', 'selectedNotification', 'notificationSummary', 'filter'));
    }

    public function openNotification(AppNotification $notification)
    {
        $user = $this->user();
        abort_unless(
            AppNotification::visibleTo($user)
                ->whereKey($notification->notification_id)
                ->exists(),
            403
        );

        $notification->update(['read_at' => now()]);

        return redirect()->route('notifications.index', ['selected' => $notification->notification_id]);
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
                $conversation->participants()->updateOrCreate(
                    ['user_id' => $participantId],
                    ['hidden_at' => null]
                );
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
        $conversation->participants()->where('user_id', $sender->user_id)->update(['hidden_at' => null]);

        $conversation->participants()->where('user_id', '!=', $sender->user_id)->with('user')->get()->each(function ($participant) use ($conversation, $sender, $body) {
            $recipientId = $participant->user_id;
            if ($participant->hidden_at !== null) {
                $participant->update(['hidden_at' => null]);
            }
            AppNotification::create(['user_id' => $recipientId, 'type' => 'message', 'title' => 'New message from ' . $sender->name, 'body' => str($body)->limit(120), 'url' => route('messages.inbox', ['conversation' => $conversation->conversation_id])]);
            if ($participant->user) {
                Mail::to($participant->user->email, $participant->user->name)->queue(new ActivityMail(
                    'New message from ' . $sender->name,
                    str($body)->limit(120)->toString() . "\n\nOpen Messages in LJS Realty to reply."
                ));
            }
        });
    }

    private function availableContacts(User $user)
    {
        return User::query()
            ->where('account_status', 'Active')
            ->whereKeyNot($user->user_id)
            ->when($user->normalizedRole() === 'client', fn ($query) => $query->whereIn('role_name', ['Broker', 'Agent', 'Lead Broker']))
            ->orderBy('name')
            ->get(['user_id', 'name', 'role_name']);
    }

    private function canMessage(User $sender, User $recipient): bool
    {
        if ($recipient->account_status !== 'Active' || $sender->user_id === $recipient->user_id) {
            return false;
        }

        return $sender->normalizedRole() !== 'client'
            || in_array($recipient->role_name, ['Broker', 'Agent', 'Lead Broker'], true);
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
