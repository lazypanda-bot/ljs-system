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
<style>
.notifications-page{max-width:900px;margin:0 auto;padding:8px}.notifications-page-header{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:22px}.notifications-page-header span{color:var(--color-primary);font-size:12px;font-weight:800;letter-spacing:.08em}.notifications-page-header h1{margin:5px 0;color:var(--color-text);font-size:28px}.notifications-page-header p{margin:0;color:var(--color-text-muted)}.notifications-messages-link{border-radius:8px;padding:10px 14px;background:var(--color-primary);color:#fff;text-decoration:none;font-weight:700}.notifications-list{overflow:hidden;border:1px solid var(--color-border);border-radius:12px;background:var(--color-surface)}.notification-page-item{display:grid;grid-template-columns:38px 1fr;gap:12px;padding:16px;border-bottom:1px solid var(--color-border);color:var(--color-text);text-decoration:none}.notification-page-item:last-child{border-bottom:0}.notification-page-item.unread{background:var(--color-surface-soft)}.notification-page-item:hover{background:var(--color-primary-soft)}.notification-page-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:9px;background:var(--color-primary);color:#fff;font-weight:800}.notification-team .notification-page-icon{background:var(--color-accent-green)}.notification-listing .notification-page-icon{background:var(--color-accent-orange)}.notification-page-item strong,.notification-page-item small,.notification-page-item time{display:block}.notification-page-item small,.notification-page-item time,.notifications-empty{margin-top:4px;color:var(--color-text-muted);font-size:13px}.notifications-empty{padding:30px;text-align:center}@media(max-width:600px){.notifications-page-header{align-items:start;flex-direction:column}}
</style>
@endpush