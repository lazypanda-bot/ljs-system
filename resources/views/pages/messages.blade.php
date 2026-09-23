@extends($isClient ? 'layouts.app' : 'layouts.client')

@section('title', $pageTitle ?? 'Messages')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/messages.css') }}?v={{ filemtime(public_path('css/messages.css')) }}">
@endpush

@section('content')
	<div class="messaging-container {{ $isClient ? 'public-messaging' : '' }}">
		<aside class="thread-list">
					<div class="thread-list-header"><span>Messages</span><button type="button" class="compose-message-btn" id="openComposeMessage" aria-label="Compose message"><i class="fas fa-pen" aria-hidden="true"></i><span>Compose</span></button></div>
			<div class="threads-container">
				@forelse ($conversations as $item)
					@php
						$lastMessage = $item->messages->first();
						$other = $item->type === 'team' ? null : $item->participants->first(fn ($participant) => $participant->user_id !== auth()->id());
						$name = $item->type === 'team' ? ($item->team?->team_name ?? 'Team chat') : ($other?->user?->name ?? 'Direct message');
						$role = $item->type === 'team' ? 'team' : ($other?->user?->normalizedRole() ?? 'user');
					@endphp
					<a href="{{ route('messages.inbox', ['conversation' => $item->conversation_id]) }}" class="thread-item role-{{ $role }} {{ $conversation?->conversation_id === $item->conversation_id ? 'active' : '' }}">
						<span class="thread-avatar role-{{ $role }}">{{ strtoupper(substr($name, 0, 1)) }}</span>
						<div class="thread-info"><div class="thread-header"><span class="thread-name">{{ $name }}</span><span class="thread-time">{{ $lastMessage?->created_at?->diffForHumans(short: true) }}</span></div><div class="thread-snippet">{{ $lastMessage?->body ?? 'No messages yet.' }}</div></div>
					</a>
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
				@endphp
				<div class="chat-header"><div class="chat-header-info"><h3>{{ $title }}</h3><span>{{ $conversation->type === 'team' ? $conversation->participants->count() . ' participants' : 'Direct conversation' }}</span></div></div>
				<div class="chat-messages" data-last-message-id="{{ $conversation->messages->last()?->message_id ?? 0 }}" data-current-user-id="{{ auth()->id() }}">
					@foreach ($conversation->messages as $message)
						<div class="message-row {{ $message->sender_id === auth()->id() ? 'message-row-sent' : 'message-row-received' }}"><span class="message-avatar role-{{ $message->sender->normalizedRole() }}">{{ strtoupper(substr($message->sender->name, 0, 1)) }}</span><div class="message-bubble role-{{ $message->sender->normalizedRole() }} {{ $message->sender_id === auth()->id() ? 'message-sent' : 'message-received' }}">@if($message->subject)<div class="message-subject">{{ $message->subject }}</div>@endif<div class="message-author">{{ $message->sender->name }} <span>{{ $message->sender->role_name }}</span></div><div class="message-text">{{ $message->body }}</div>@if($message->attachment_path)<a class="message-attachment" href="{{ asset('storage/' . $message->attachment_path) }}" target="_blank"><i class="fas fa-paperclip"></i> Attachment</a>@endif<div class="message-meta">{{ $message->created_at->format('M d, g:i A') }}</div></div></div>
					@endforeach
				</div>
				<form class="reply-box" method="POST" action="{{ route('messages.store', $conversation) }}">
					@csrf
					<div class="reply-input-wrapper"><textarea class="reply-input" name="body" placeholder="Type a message..." rows="1" maxlength="4000" required></textarea></div>
					<button class="btn-icon btn-send" title="Send message" type="submit" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
				</form>
			@else
				<div class="empty-chat-panel">Select a conversation or start a new one.</div>
			@endif
		</section>
	</div>
	<div class="compose-modal" id="composeMessageModal" aria-hidden="true">
		<form method="POST" action="{{ route('messages.compose') }}" enctype="multipart/form-data" class="compose-dialog">
			@csrf
			<div class="compose-header"><div><h2>New message</h2><p>Start a private conversation.</p></div><button type="button" class="compose-close" aria-label="Close">&times;</button></div>
			<label for="recipientId">To</label>
			<select id="recipientId" name="recipient_id" required><option value="">Choose a recipient</option>@foreach($contacts as $contact)<option value="{{ $contact->user_id }}">{{ $contact->name }} - {{ $contact->role_name }}</option>@endforeach</select>
			<label for="messageSubject">Subject</label><input id="messageSubject" name="subject" maxlength="255" placeholder="What is this about?">
			<label for="messageBody">Message</label><textarea id="messageBody" name="body" rows="6" maxlength="4000" required placeholder="Write your message..."></textarea>
			<label for="messageAttachment" class="compose-attachment"><i class="fas fa-paperclip"></i> Attach a file<input id="messageAttachment" name="attachment" type="file"></label>
			<div class="compose-actions"><button type="button" class="compose-cancel">Cancel</button><button type="submit" class="compose-send"><i class="fas fa-paper-plane"></i> Send message</button></div>
		</form>
	</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/messages.js') }}?v={{ filemtime(public_path('js/messages.js')) }}"></script>
@endpush
