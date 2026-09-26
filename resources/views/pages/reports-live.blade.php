@extends('layouts.client')
@section('title', 'Reports')
@section('content')
<div class="live-report-page staff-page-surface"><div class="report-stat-grid"><div><strong>{{ $properties->count() }}</strong><span>Properties</span></div><div><strong>{{ $appointments->count() }}</strong><span>Appointments</span></div><div><strong>{{ $soldProperties->count() }}</strong><span>Sold</span></div><div><strong>{{ $properties->pluck('user_id')->unique()->count() }}</strong><span>Professionals</span></div></div><a class="report-export-btn" href="{{ route('reports.export') }}"><i class="fas fa-file-csv"></i> Export</a></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/staff-pages.css') }}?v={{ filemtime(public_path('css/staff-pages.css')) }}">@endpush