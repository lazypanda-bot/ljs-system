<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Messages</title>
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ filemtime(public_path('css/global.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/messages.css') }}?v={{ filemtime(public_path('css/messages.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/icons.css') }}?v={{ filemtime(public_path('css/icons.css')) }}">
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('js/icons.js') }}?v={{ filemtime(public_path('js/icons.js')) }}" defer></script>
    <script src="{{ asset('js/global.js') }}?v={{ filemtime(public_path('js/global.js')) }}" defer></script>
</head>
<body class="client-messages-embed">
    <main>@include('pages.message-workspace', ['panelMode' => true])</main>
    <script src="{{ asset('js/messages.js') }}?v={{ filemtime(public_path('js/messages.js')) }}" defer></script>
</body>
</html>
