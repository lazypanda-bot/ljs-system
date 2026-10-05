<div class="messaging-container {{ $isClient ? 'public-messaging' : '' }} {{ ($panelMode ?? false) ? 'client-messages-panel' : '' }} {{ $conversation && request()->filled('conversation') ? 'has-selected-thread' : '' }}">
    <aside class="thread-list" aria-label="Conversations">
        <div class="thread-list-header"><span>Messages</span><button type="button" class="compose-message-btn" id="openComposeMessage" aria-label="Compose message"><i class="fas fa-pen" aria-hidden="true"></i><span>Compose</span></button></div>
        <form class="thread-filters" method="GET" action="{{ route('messages.inbox') }}" role="search">
            @if ($panelMode ?? false)<input type="hidden" name="panel" value="1">@endif
            <label class="visually-hidden" for="threadSearch">Search messages</label>
            <input id="threadSearch" type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search people, properties, or messages">
        </form>
        <div class="threads-container">
            @forelse ($conversations as $item)
                @php
                    $lastMessage = $item->messages->first();
                    $propertyNames = $item->propertyInquiries->pluck('property.property_name')->filter()->unique();
                    $snippet = $lastMessage?->body ?? ($propertyNames->isNotEmpty() ? $propertyNames->join(', ') : 'No messages yet.');
                    $snippet = \Illuminate\Support\Str::limit($snippet, 90);
                    $other = $item->type === 'team' ? null : $item->participants->first(fn ($participant) => $participant->user_id !== auth()->id());
                    $name = $item->type === 'team' ? ($item->team?->team_name ?? 'Team chat') : ($other?->user?->name ?? 'Direct message');
                    $role = $item->type === 'team' ? 'team' : ($other?->user?->normalizedRole() ?? 'user');
                    $conversationUrl = ($panelMode ?? false)
                        ? route('messages.inbox', ['conversation' => $item->conversation_id, 'panel' => 1])
                        : route('messages.inbox', ['conversation' => $item->conversation_id]);
                    $removeConversationUrl = route('messages.conversations.delete', [
                        'conversation' => $item->conversation_id,
                        'panel' => ($panelMode ?? false) ? 1 : null,
                    ]);
                @endphp
                <div class="thread-entry role-{{ $role }} {{ $conversation?->conversation_id === $item->conversation_id ? 'active' : '' }}">
                    <a href="{{ $conversationUrl }}" class="thread-item role-{{ $role }} {{ $conversation?->conversation_id === $item->conversation_id ? 'active' : '' }}">
                        <span class="thread-avatar role-{{ $role }}">{{ strtoupper(substr($name, 0, 1)) }}</span>
                        <div class="thread-info"><div class="thread-header"><span class="thread-name">{{ $name }}</span><span class="thread-time">{{ $lastMessage?->created_at?->diffForHumans(short: true) }}</span></div><div class="thread-snippet">{{ $snippet }}</div></div>
                    </a>
                    <div class="thread-menu-wrap">
                        <button type="button" class="thread-menu-toggle" aria-label="Conversation actions" aria-haspopup="menu" aria-expanded="false" data-thread-menu-toggle><i data-lucide="ellipsis" aria-hidden="true"></i></button>
                        <div class="thread-action-menu" role="menu" hidden>
                            <form method="POST" action="{{ $removeConversationUrl }}" data-confirm-message="Remove this conversation from your inbox? Other participants will keep their conversation history.">
                                @csrf @method('DELETE')
                                        <button type="submit" role="menuitem">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="empty-chat">No conversations yet.</p>
            @endforelse
        </div>
    </aside>

    <section class="chat-view" @if($conversation) data-conversation-id="{{ $conversation->conversation_id }}" data-updates-url="{{ route('messages.updates', $conversation) }}" @endif>
        @if ($conversation)
            @php
                $other = $conversation->type === 'team' ? null : $conversation->participants->first(fn ($participant) => $participant->user_id !== auth()->id());
                $title = $conversation->type === 'team' ? ($conversation->team?->team_name ?? 'Team chat') : ($other?->user?->name ?? 'Direct message');
                $replyUrl = ($panelMode ?? false)
                    ? route('messages.store', ['conversation' => $conversation, 'panel' => 1])
                    : route('messages.store', $conversation);
            @endphp
            <div class="chat-header">
                <a class="thread-back" href="{{ route('messages.inbox', ['panel' => ($panelMode ?? false) ? 1 : null]) }}" aria-label="Back to conversations"><i data-lucide="arrow-left" aria-hidden="true"></i></a>
                <div class="chat-header-info"><h3>{{ $title }}</h3>@if ($conversation->type === 'team')<span>{{ $conversation->participants->count() }} participants</span>@endif</div>
            </div>
            <div class="chat-messages" data-last-message-id="{{ $conversation->messages->last()?->message_id ?? 0 }}" data-current-user-id="{{ auth()->id() }}">
                @foreach ($conversation->messages as $message)
                    @php($isOwnMessage = (int) $message->sender_id === (int) auth()->id())
                    <div class="message-row {{ $isOwnMessage ? 'message-row-sent' : 'message-row-received' }}">
                        <span class="message-avatar role-{{ $message->sender->normalizedRole() }}">{{ strtoupper(substr($message->sender->name, 0, 1)) }}</span>
                        <div class="message-bubble role-{{ $message->sender->normalizedRole() }} {{ $isOwnMessage ? 'message-sent' : 'message-received' }}">
                            @if ($message->subject)<div class="message-subject">{{ $message->subject }}</div>@endif
                            <div class="message-author">{{ $message->sender->name }} <span>{{ $message->sender->role_name }}</span></div>
                            <div class="message-text" data-message-display>{{ $message->body }}</div>
                            @if ($message->attachment_path)
                                <a class="message-attachment" href="{{ route('messages.attachments.download', $message) }}"><i class="fas fa-paperclip" aria-hidden="true"></i> Attachment</a>
                            @endif
                            <div class="message-meta">{{ $message->created_at->format('M d, g:i A') }}</div>
                            @if ($isOwnMessage)
                                <form method="POST" action="{{ route('messages.update', ['message' => $message, 'panel' => ($panelMode ?? false) ? 1 : null]) }}" class="message-edit-form" data-message-edit-form hidden>
                                    @csrf @method('PATCH')
                                    <label class="visually-hidden" for="editMessage{{ $message->message_id }}">Edit message</label>
                                    <textarea id="editMessage{{ $message->message_id }}" name="body" maxlength="4000" required>{{ $message->body }}</textarea>
                                    <div><button type="submit" class="message-edit-save">Save</button><button type="button" class="message-edit-cancel" data-cancel-message-edit>Cancel</button></div>
                                </form>
                            @endif
                        </div>
                        @if ($isOwnMessage)
                            <div class="message-actions">
                                <button type="button" class="message-menu-toggle" aria-label="Message actions" aria-haspopup="menu" aria-expanded="false" data-message-menu-toggle><i data-lucide="ellipsis" aria-hidden="true"></i></button>
                                <div class="message-action-menu" role="menu" hidden>
                                    <button type="button" role="menuitem" data-edit-message>Edit</button>
                                    <form method="POST" action="{{ route('messages.destroy', ['message' => $message, 'panel' => ($panelMode ?? false) ? 1 : null]) }}" data-confirm-message="Delete this message for everyone in the conversation? This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button type="submit" role="menuitem" class="message-action-delete">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <form class="reply-box" method="POST" action="{{ $replyUrl }}">
                @csrf
                <div class="reply-input-wrapper"><textarea class="reply-input" name="body" placeholder="Type a message..." rows="1" maxlength="4000" required></textarea></div>
                <button class="message-send-button" type="submit"><span>Send</span></button>
            </form>
        @else
            <div class="empty-chat-panel">Select a conversation or start a new one.</div>
        @endif
    </section>
</div>

<div class="compose-modal" id="composeMessageModal" aria-hidden="true">
    @php($composeUrl = ($panelMode ?? false) ? route('messages.compose', ['panel' => 1]) : route('messages.compose'))
    <form method="POST" action="{{ $composeUrl }}" enctype="multipart/form-data" class="compose-dialog">
        @csrf
        <div class="compose-header"><div><h2>New message</h2><p>Start a private conversation.</p></div><button type="button" class="compose-close" aria-label="Close">&times;</button></div>
        <label for="recipientId">To</label>
        <select id="recipientId" name="recipient_id" required><option value="">Choose a recipient</option>@foreach($contacts as $contact)<option value="{{ $contact->user_id }}">{{ $contact->name }} - {{ $contact->role_name }}</option>@endforeach</select>
        <label for="messageSubject">Subject</label><input id="messageSubject" name="subject" maxlength="255" placeholder="What is this about?">
        <label for="messageBody">Message</label><textarea id="messageBody" name="body" rows="6" maxlength="4000" required placeholder="Write your message..."></textarea>
        <label for="messageAttachment" class="compose-attachment"><i class="fas fa-paperclip" aria-hidden="true"></i> Attach a file<input id="messageAttachment" name="attachment" type="file"></label>
        <div class="compose-actions"><button type="button" class="compose-cancel">Cancel</button><button type="submit" class="compose-send">Send message</button></div>
    </form>
</div>
