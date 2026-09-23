@php
    $flashMessages = collect([
        'success' => session('success'),
        'warning' => session('warning'),
        'error' => session('error'),
        'info' => session('info'),
        'status' => session('status'),
    ])->filter();
@endphp

@if ($flashMessages->isNotEmpty())
    <div class="flash-toast-stack" aria-live="polite" aria-atomic="true">
        @foreach ($flashMessages as $type => $message)
            <div class="flash-toast flash-toast-{{ $type }}" role="{{ in_array($type, ['error', 'warning']) ? 'alert' : 'status' }}">
                <strong>{{ ucfirst($type) }}</strong>
                <span>{{ $message }}</span>
                <button type="button" class="flash-toast-close" aria-label="Dismiss notification">&times;</button>
            </div>
        @endforeach
    </div>
@endif
