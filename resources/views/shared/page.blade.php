@extends('layouts.client')

@section('content')
    <div class="container">
        <h1>{{ $pageTitle }}</h1>

        @php
            $includePath = strtolower($acting) . '.' . strtolower($acting) . '-' . str_replace('_', '-', strtolower($page));
        @endphp

        @include($includePath)
    </div>
@endsection