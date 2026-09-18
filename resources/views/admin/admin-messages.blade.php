@extends('layouts.client')

@section('title', 'Admin Messages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/messages.css') }}">
@endpush

@section('content')
<div class="messaging-container">
    <!-- Left Thread List -->
    <div class="thread-list">
        <div class="thread-list-header">
            Messages
        </div>
        <div class="threads-container">
            <!-- Example Thread 1 -->
            <div class="thread-item active">
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=John+Doe&background=random" alt="JD">
                </div>
                <div class="thread-info">
                    <div class="thread-header">
                        <span class="thread-name">John Doe</span>
                        <span class="thread-time">10:45 AM</span>
                    </div>
                    <div class="thread-snippet">Hey, I'm interested in the property on Oak Street.</div>
                </div>
            </div>
            
            <!-- Example Thread 2 -->
            <div class="thread-item">
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=Jane+Smith&background=random" alt="JS">
                </div>
                <div class="thread-info">
                    <div class="thread-header">
                        <span class="thread-name">Jane Smith</span>
                        <span class="thread-time">Yesterday</span>
                    </div>
                    <div class="thread-snippet">Can we schedule a viewing for tomorrow?</div>
                </div>
            </div>

            <!-- Example Thread 3 -->
            <div class="thread-item">
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=Robert+Brown&background=random" alt="RB">
                </div>
                <div class="thread-info">
                    <div class="thread-header">
                        <span class="thread-name">Robert Brown</span>
                        <span class="thread-time">Monday</span>
                    </div>
                    <div class="thread-snippet">Thanks for the information!</div>
                </div>
            </div>
        </div>
    </div>

        <!-- Right Chat View -->
    <div class="chat-view">
        <div class="chat-header">
            <div class="avatar" style="width: 40px; height: 40px;">
                <img src="https://ui-avatars.com/api/?name=John+Doe&background=random" alt="JD">
            </div>
            <div class="chat-header-info">
                <h3>John Doe</h3>
                <span>Online</span>
            </div>
        </div>


        <div class="chat-messages">
            <div class="message-bubble message-received">
                <div class="message-text">Hi, I saw your listing for the property on Oak Street. Is it still available?</div>
                <div class="message-meta">10:30 AM</div>
            </div>
            
            <div class="message-bubble message-sent">
                <div class="message-text">Hello John! Yes, it is still available. Would you like to schedule a tour?</div>
                <div class="message-meta">10:35 AM</div>
            </div>

            <div class="message-bubble message-received">
                <div class="message-text">Yes, that would be great. I'm free this afternoon around 3 PM.</div>
                <div class="message-meta">10:45 AM</div>
            </div>
        </div>

        <div class="reply-box">
            <div class="reply-input-wrapper">
                <textarea class="reply-input" placeholder="Type a message..." rows="1"></textarea>
            </div>
            <div class="reply-actions">
                <button class="btn-icon btn-attach" title="Attach file">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                </button>
                <button class="btn-icon btn-send" title="Send message">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/messages.js') }}"></script>
@endpush

