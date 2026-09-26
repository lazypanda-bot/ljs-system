@extends('layouts.client')

@section('title', 'My Agents')

@section('content')
    <div class="managed-agents-page staff-page-surface">
        <div class="managed-agents-grid">
            @forelse ($managedAgents as $agent)
                @php $photo = $agent->profile_photo ? asset('storage/' . $agent->profile_photo) : asset('images/ljs-logo.jpg'); @endphp
                <article class="managed-agent-card"><img src="{{ $photo }}" alt="{{ $agent->name }}"><div><h2>{{ $agent->name }}</h2><p>{{ $agent->role_name }}</p><small>{{ $agent->professionalProfile?->years_experience ?? 0 }} years experience</small></div><a href="{{ route('agents.show', $agent) }}">View profile</a></article>
            @empty
                <p class="managed-agents-empty">No approved team members yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">
@endpush