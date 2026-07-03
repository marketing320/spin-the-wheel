@props(['title' => 'Live', 'jsEntry' => 'resources/js/app.js'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <x-head-fonts />
    @vite(array_merge(['resources/css/app.css'], (array) $jsEntry))
</head>
<body class="h-screen overflow-hidden bg-white antialiased">
    {{ $slot }}
    <x-lucide-scripts />
</body>
</html>
