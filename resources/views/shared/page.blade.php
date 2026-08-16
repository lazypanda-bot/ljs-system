@extends('layouts.client')

@section('content')
    <div class="container">
        <h1>{{ $pageTitle }}</h1>
        
        {{-- Dynamically load the specific view based on role and page name --}}
        @include("{$acting}.{$acting}-{$page}")
    </div>
@endsection