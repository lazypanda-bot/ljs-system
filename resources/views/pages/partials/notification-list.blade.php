<nav class="notifications-list" aria-label="Notification list">
    @forelse ($notifications as $notification)
        <a class="notification-list-item notification-{{ $notification->type }} {{ $notification->read_at ? 'read' : 'unread' }} {{ (int) ($selectedNotification?->notification_id) === (int) $notification->notification_id ? 'active' : '' }}"
           href="{{ route('notifications.index', ['selected' => $notification->notification_id]) }}"
           @if ((int) ($selectedNotification?->notification_id) === (int) $notification->notification_id) aria-current="true" @endif>
            <span class="notification-page-icon">{{ $notification->type === 'message' ? 'M' : ($notification->type === 'team' ? 'T' : ($notification->appointment_proposal_id ? 'A' : 'L')) }}</span>
            <span class="notification-list-copy"><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span>
        </a>
    @empty
        <p class="notifications-empty">You have no notifications yet.</p>
    @endforelse
    {{ $notifications->links('pagination.notifications') }}
    <p class="notification-pagination-status" role="status" aria-live="polite"></p>
</nav>