@props(['title' => 'Live', 'jsEntry' => 'resources/js/app.js'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>

    {{-- Installable desktop app (live-view / roadshow-live only — see
         public/manifest-live.json and public/sw-live.js). No offline
         caching: this display is realtime/server-driven, so a cache layer
         would risk showing stale spin/queue state instead of any benefit. --}}
    <link rel="manifest" href="{{ asset('manifest-live.json') }}">
    <meta name="theme-color" content="#eb242a">
    <link rel="icon" href="{{ asset('icons/live-icon-32.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('icons/live-icon-180.png') }}">

    <script src="{{ asset('js/confettea.min.js') }}?v={{ @filemtime(public_path('js/confettea.min.js')) ?: '1' }}"></script>
    <script src="https://cdn.lordicon.com/lordicon.js"></script>
    <x-head-fonts />
    @vite(array_merge(['resources/css/app.css'], (array) $jsEntry))
</head>
<body class="live-surface h-screen overflow-hidden bg-white antialiased">
    {{ $slot }}
    <x-lucide-scripts />
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw-live.js') }}'));
        }
    </script>
</body>
</html>
