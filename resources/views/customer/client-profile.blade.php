@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/client-profile.css') }}">
@endpush

@section('content')
<div class="profile-container">
    
    <div class="profile-card">
        <h2 class="profile-header-title">Client Profile</h2>
        
        <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="profile-avatar-wrap">
                <div class="avatar role-client profile-avatar-box">
                    @if (auth()->user()->profile_photo)
                        <img src="{{ str_starts_with(auth()->user()->profile_photo, 'http') ? auth()->user()->profile_photo : asset('storage/' . auth()->user()->profile_photo) }}" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px;">Change Profile Photo</label>
                    <input type="file" name="profile_photo" style="font-size: 13px;">
                </div>
            </div>

            <div class="profile-form-grid">
                <div class="profile-form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ auth()->user()->name }}">
                </div>
                <div class="profile-form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ auth()->user()->email }}">
                </div>
            </div>

            <button type="submit" class="profile-submit-btn">Save Changes</button>
        </form>
    </div>

    <div class="profile-card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px; color: var(--color-text);">My Appointments History</h3>
        @forelse($appointments ?? [] as $appointment)
            <div class="profile-appointment-item">
                <div>
                    <p style="font-weight: 600; font-size: 14px;">{{ $appointment->property->title ?? 'Property Viewing' }}</p>
                    <small style="color: var(--color-text-muted);">{{ $appointment->appointment_date ?? 'Date TBD' }}</small>
                </div>
                <span class="profile-badge-pending">{{ ucfirst($appointment->status ?? 'Pending') }}</span>
            </div>
        @empty
            <p style="color: var(--color-text-muted); font-size: 13px;">No appointments booked yet.</p>
        @endforelse
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/client-profile.js') }}" defer></script>
@endpush