@extends($isClient ? 'layouts.app' : 'layouts.client')

@section('title', $pageTitle ?? 'Messages')

@push('styles')
	<link rel="stylesheet" href="{{ asset('css/messages.css') }}?v={{ filemtime(public_path('css/messages.css')) }}">
@endpush

@section('content')
	@include('pages.message-workspace', ['panelMode' => false])
@endsection

@push('scripts')
	<script src="{{ asset('js/messages.js') }}?v={{ filemtime(public_path('js/messages.js')) }}"></script>
@endpush
