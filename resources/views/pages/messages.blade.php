@extends('layouts.client')

@section('title', $pageTitle ?? 'Messages')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/messages.css') }}">
@endpush

@section('content')
	<div class="messaging-container">
		<div class="thread-list">
			<div class="thread-list-header">Messages</div>
			<div class="threads-container">
				@foreach ([
					['name' => 'John Doe', 'time' => '10:45 AM', 'message' => "Hey, I'm interested in the property on Oak Street."],
					['name' => 'Jane Smith', 'time' => 'Yesterday', 'message' => 'Can we schedule a viewing for tomorrow?'],
					['name' => 'Robert Brown', 'time' => 'Monday', 'message' => 'Thanks for the information!'],
				] as $index => $thread)
					<div class="thread-item {{ $index === 0 ? 'active' : '' }}">
						<div class="avatar"><img src="https://ui-avatars.com/api/?name={{ urlencode($thread['name']) }}&background=random" alt="{{ $thread['name'] }}"></div>
						<div class="thread-info">
							<div class="thread-header"><span class="thread-name">{{ $thread['name'] }}</span><span class="thread-time">{{ $thread['time'] }}</span></div>
							<div class="thread-snippet">{{ $thread['message'] }}</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>

		<div class="chat-view">
			<div class="chat-header">
				<div class="avatar" style="width: 40px; height: 40px;"><img src="https://ui-avatars.com/api/?name=John+Doe&background=random" alt="John Doe"></div>
				<div class="chat-header-info"><h3>John Doe</h3><span>Online</span></div>
			</div>
			<div class="chat-messages">
				<div class="message-bubble message-received"><div class="message-text">Hi, I saw your listing for the property on Oak Street. Is it still available?</div><div class="message-meta">10:30 AM</div></div>
				<div class="message-bubble message-sent"><div class="message-text">Hello John! Yes, it is still available. Would you like to schedule a tour?</div><div class="message-meta">10:35 AM</div></div>
				<div class="message-bubble message-received"><div class="message-text">Yes, that would be great. I'm free this afternoon around 3 PM.</div><div class="message-meta">10:45 AM</div></div>
			</div>
			<div class="reply-box">
				<div class="reply-input-wrapper"><textarea class="reply-input" placeholder="Type a message..." rows="1"></textarea></div>
				<div class="reply-actions">
					<button class="btn-icon btn-attach" title="Attach file" type="button" aria-label="Attach file"><i class="fas fa-paperclip"></i></button>
					<button class="btn-icon btn-send" title="Send message" type="button" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script src="{{ asset('js/messages.js') }}"></script>
@endpush
