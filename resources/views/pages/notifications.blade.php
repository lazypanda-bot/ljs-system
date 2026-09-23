@extends('layouts.client')

@section('title', 'Notifications')

@section('content')
    <div class="notifications-page">
        <div class="notifications-page-header">
            <div><span>ACCOUNT UPDATES</span><h1>Notifications</h1><p>New listings, messages, team updates, and account activity.</p></div>
            <a href="{{ route('messages.inbox') }}" class="notifications-messages-link"><i class="fas fa-paper-plane"></i> Messages</a>
        </div>
        <div class="notifications-list">
            @forelse ($notifications as $notification)
                <a class="notification-page-item notification-{{ $notification->type }} {{ $notification->read_at ? 'read' : 'unread' }}" href="{{ route('notifications.open', $notification) }}">
                    <span class="notification-page-icon">{{ $notification->type === 'message' ? 'M' : ($notification->type === 'team' ? 'T' : 'L') }}</span>
                    <span><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span>
                </a>
            @empty
                <p class="notifications-empty">You have no notifications yet.</p>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">
@endpush